<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/peminjamans  (Bearer Token)
     * ?status=dipinjam|dikembalikan  ?nim=  ?per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'nullable|in:dipinjam,dikembalikan',
            'nim'      => 'nullable|string|max:15',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $paginator = Peminjaman::query()
            ->with('buku') // bebas N+1: judul buku dimuat sekaligus
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('nim'), fn ($q, $nim) => $q->where('nim_peminjam', strtoupper(trim($nim))))
            ->latest('id')
            ->paginate((int) $request->query('per_page', 10))
            ->withQueryString();

        return $this->successResponse(
            data: PeminjamanResource::collection($paginator->items()),
            message: 'Daftar transaksi peminjaman berhasil diambil.',
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total_items'  => $paginator->total(),
                'total_pages'  => $paginator->lastPage(),
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
     * GET /api/v1/peminjamans/{id}  (Bearer Token)
     */
    public function show(string $id): JsonResponse
    {
        $peminjaman = Peminjaman::with('buku')->find($id);

        if (!$peminjaman) {
            return $this->peminjamanTidakDitemukan($id);
        }

        return $this->successResponse(
            data: new PeminjamanResource($peminjaman),
            message: 'Detail transaksi peminjaman berhasil ditemukan.'
        );
    }

    /**
     * POST /api/v1/peminjamans  (Bearer Token)
     * Pinjam buku secara atomik: kunci baris buku -> cek -> stok-1 -> catat.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id'       => 'required|integer|exists:bukus,id',
            'nim_peminjam'  => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'durasi_hari'   => 'required|integer|in:3,7,14',
        ]);

        $nim = strtoupper(trim($validated['nim_peminjam']));

        return DB::transaction(function () use ($validated, $nim) {
            // 1. SELECT ... FOR UPDATE: transaksi lain yang mengincar buku ini harus antre
            $buku = Buku::whereKey($validated['buku_id'])->lockForUpdate()->first();

            // 2. Cek stok SETELAH baris terkunci (bukan sebelumnya) -> 422
            if ($buku->stok < 1) {
                return $this->problemResponse(
                    title: 'Book Out of Stock',
                    detail: "Stok buku \"{$buku->judul}\" sedang habis.",
                    status: 422,
                    errorSlug: 'stock-exhausted'
                );
            }

            // 3. NIM yang sama masih meminjam buku yang sama -> 409
            $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->exists();

            if ($masihDipinjam) {
                return $this->problemResponse(
                    title: 'Duplicate Loan Conflict',
                    detail: "NIM {$nim} masih meminjam \"{$buku->judul}\" dan belum mengembalikannya.",
                    status: 409,
                    errorSlug: 'duplicate-loan'
                );
            }

            // 4. Dua penulisan yang harus sukses bersama (atau gagal bersama)
            $buku->decrement('stok');

            $peminjaman = Peminjaman::create([
                'kode_pinjam'         => 'PJM-' . now()->format('ymd') . '-' . Str::upper(Str::random(5)),
                'buku_id'             => $buku->id,
                'nim_peminjam'        => $nim,
                'nama_peminjam'       => $validated['nama_peminjam'],
                'tanggal_pinjam'      => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays((int) $validated['durasi_hari'])->toDateString(),
                'status'              => 'dipinjam',
            ]);

            $peminjaman->setRelation('buku', $buku);

            return $this->createdResponse(
                data: new PeminjamanResource($peminjaman),
                locationUrl: url("/api/v1/peminjamans/{$peminjaman->id}"),
                message: "Peminjaman berhasil dicatat. Sisa stok \"{$buku->judul}\": {$buku->stok}."
            );
        });
    }

    /**
     * PATCH /api/v1/peminjamans/{id}/kembalikan  (Bearer Token)
     * Kembalikan buku secara atomik: kunci buku & peminjaman -> status -> stok+1.
     */
    public function kembalikan(string $id): JsonResponse
    {
        $bukuId = Peminjaman::whereKey($id)->value('buku_id');

        if (!$bukuId) {
            return $this->peminjamanTidakDitemukan($id);
        }

        return DB::transaction(function () use ($id, $bukuId) {
            // Urutan kunci SAMA dengan proses pinjam (buku dulu) -> mencegah deadlock
            $buku       = Buku::whereKey($bukuId)->lockForUpdate()->first();
            $peminjaman = Peminjaman::whereKey($id)->lockForUpdate()->first();

            // Cek status SETELAH terkunci: dua klik "kembalikan" bersamaan
            // hanya akan diproses sekali, yang kedua mendapat 409
            if ($peminjaman->status === 'dikembalikan') {
                return $this->problemResponse(
                    title: 'Already Returned Conflict',
                    detail: "Peminjaman {$peminjaman->kode_pinjam} sudah dikembalikan pada {$peminjaman->tanggal_kembali?->toDateString()}.",
                    status: 409,
                    errorSlug: 'already-returned'
                );
            }

            $hariTerlambat = max(0, (int) $peminjaman->tanggal_jatuh_tempo->diffInDays(today(), false));

            $peminjaman->update([
                'status'          => 'dikembalikan',
                'tanggal_kembali' => today()->toDateString(),
            ]);
            $buku?->increment('stok');

            $peminjaman->setRelation('buku', $buku);

            return $this->successResponse(
                data: new PeminjamanResource($peminjaman),
                message: $hariTerlambat > 0
                    ? "Buku berhasil dikembalikan, terlambat {$hariTerlambat} hari."
                    : 'Buku berhasil dikembalikan tepat waktu.'
            );
        });
    }

    private function peminjamanTidakDitemukan(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Transaksi peminjaman dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'loan-not-found'
        );
    }
}