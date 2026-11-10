<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BukuApiController extends Controller
{
    use ApiResponse;

    /** Kolom yang boleh dipakai ?sort= (allowlist mencegah SQL injection di ORDER BY) */
    private const SORTABLE = ['judul', 'penulis', 'tahun_terbit', 'stok', 'created_at'];

    /**
     * Hitung total riwayat & pinjaman aktif dalam SATU query (bebas N+1).
     */
    private function withHitungan(Builder $query): Builder
    {
        return $query->withCount([
            'peminjamans',
            'peminjamans as peminjaman_aktif_count' => fn ($q) => $q->where('status', 'dipinjam'),
        ]);
    }

    /**
     * GET /api/v1/bukus  (publik)
     * ?search= ?kategori_id= ?tersedia=1 ?sort= ?dir=asc|desc ?per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $sortBy  = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'judul';
        $sortDir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $paginator = $this->withHitungan(Buku::query()->with('kategori'))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('judul', 'like', "%{$search}%")
                        ->orWhere('penulis', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($request->query('kategori_id'), fn ($q, $id) => $q->where('kategori_id', (int) $id))
            ->when($request->boolean('tersedia'), fn ($q) => $q->where('stok', '>', 0))
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id') // urutan stabil saat nilai sort sama
            ->paginate($perPage)
            ->withQueryString();

        return $this->successResponse(
            data: BukuResource::collection($paginator->items()),
            message: 'Daftar koleksi buku berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total_items'  => $paginator->total(),
                'total_pages'  => $paginator->lastPage(),
                'sort'         => "{$sortBy},{$sortDir}",
                'links'        => [
                    'first' => $paginator->url(1),
                    'prev'  => $paginator->previousPageUrl(),
                    'next'  => $paginator->nextPageUrl(),
                    'last'  => $paginator->url($paginator->lastPage()),
                ],
            ]
        );
    }

    /**
     * GET /api/v1/bukus/{id}  (publik)
     * Detail buku + kategori + riwayat peminjaman.
     */
    public function show(string $id): JsonResponse
    {
        $buku = $this->withHitungan(Buku::query())
            ->with([
                'kategori',
                'peminjamans' => fn ($q) => $q->latest('id'),
            ])
            ->find($id);

        if (!$buku) {
            return $this->bukuTidakDitemukan($id);
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/bukus  (Bearer Token)
     * 201 Created + header Location.
     */
    public function store(Request $request): JsonResponse
    {
        $this->normalisasiIsbnInput($request);
        $validated = $request->validate($this->aturanValidasi());

        $buku = Buku::create($validated);
        $buku->load('kategori');

        return $this->createdResponse(
            data: new BukuResource($buku),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: 'Buku baru berhasil ditambahkan ke katalog.'
        );
    }

    /**
     * PUT  /api/v1/bukus/{id} -> Full Replacement (semua atribut wajib)
     * PATCH /api/v1/bukus/{id} -> Partial Update (hanya atribut yang dikirim)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return $this->bukuTidakDitemukan($id);
        }

        $this->normalisasiIsbnInput($request);
        $isPut = $request->isMethod('put');

        if ($isPut) {
            // SEMANTIK PUT: seluruh representasi dikirim utuh
            $validated = $request->validate($this->aturanValidasi($buku->id));
            // Field opsional yang tidak dikirim dianggap dikosongkan (null)
            $validated['sinopsis'] = $request->input('sinopsis');
        } else {
            // SEMANTIK PATCH: 'sometimes' -> hanya divalidasi bila dikirim
            $aturan = collect($this->aturanValidasi($buku->id))
                ->map(fn ($rule) => "sometimes|{$rule}")
                ->all();
            $validated = $request->validate($aturan);

            if (empty($validated)) {
                return $this->problemResponse(
                    title: 'Bad Request',
                    detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                    status: 400,
                    errorSlug: 'empty-patch-payload'
                );
            }
        }

        $buku->update($validated);
        $buku->load('kategori');

        return $this->successResponse(
            data: new BukuResource($buku),
            message: $isPut
                ? 'Seluruh representasi buku berhasil diganti (PUT).'
                : 'Atribut buku berhasil diperbarui sebagian (PATCH).'
        );
    }

    /**
     * DELETE /api/v1/bukus/{id}  (Bearer Token)
     * 409 jika masih ada pinjaman aktif, 204 jika berhasil.
     */
    public function destroy(string $id): JsonResponse|Response
    {
        $buku = $this->withHitungan(Buku::query())->find($id);

        if (!$buku) {
            return $this->bukuTidakDitemukan($id);
        }

        if ($buku->peminjaman_aktif_count > 0) {
            return $this->problemResponse(
                title: 'Conflict - Book Has Active Loans',
                detail: "Buku \"{$buku->judul}\" tidak dapat dihapus karena masih dipinjam oleh {$buku->peminjaman_aktif_count} mahasiswa.",
                status: 409,
                errorSlug: 'book-has-active-loans'
            );
        }

        $buku->delete();

        return $this->noContentResponse();
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    private function aturanValidasi(?int $abaikanId = null): array
    {
        $unikIsbn = 'unique:bukus,isbn' . ($abaikanId ? ",{$abaikanId}" : '');
        $tahunMaks = now()->year + 1;

        return [
            'isbn'         => "required|string|max:20|{$unikIsbn}",
            'judul'        => 'required|string|max:200',
            'penulis'      => 'required|string|max:150',
            'penerbit'     => 'required|string|max:150',
            'tahun_terbit' => "required|integer|min:1900|max:{$tahunMaks}",
            'kategori_id'  => 'required|integer|exists:kategoris,id',
            'stok'         => 'required|integer|min:0|max:1000',
            'sinopsis'     => 'nullable|string|max:2000',
        ];
    }

    /**
     * Normalisasi ISBN SEBELUM validasi, supaya aturan unique membandingkan
     * format yang sama dengan yang tersimpan ("978-602-..." == "978602...").
     */
    private function normalisasiIsbnInput(Request $request): void
    {
        if (is_string($request->input('isbn'))) {
            $request->merge([
                'isbn' => strtoupper(preg_replace('/[\s-]/', '', $request->input('isbn'))),
            ]);
        }
    }

    private function bukuTidakDitemukan(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Buku dengan ID '{$id}' tidak ditemukan di katalog.",
            status: 404,
            errorSlug: 'book-not-found'
        );
    }
}