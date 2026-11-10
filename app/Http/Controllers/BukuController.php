<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BukuController extends Controller
{
   public function index(Request $request)
{
    // State sesi: ingat pencarian terakhir
    if ($request->filled('search')) {
        session(['last_search' => $request->search]);
    }

    $bukus = Buku::with('kategori')
        ->withCount('peminjamans')
        ->when($request->search, function ($q, $s) {
            // Dikelompokkan supaya OR tidak menembus filter kategori
            $q->where(function ($w) use ($s) {
                $w->where('judul', 'like', "%{$s}%")
                  ->orWhere('penulis', 'like', "%{$s}%")
                  ->orWhere('isbn', 'like', "%{$s}%");
            });
        })
        ->when($request->kategori, fn ($q, $k) => $q->where('kategori_id', $k))
        ->latest()
        ->paginate(10)
        ->withQueryString();

    $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

    // Statistik: Query Builder, tanpa Model
    $stats = [
        'total_judul'        => DB::table('bukus')->count(),
        'total_stok'         => DB::table('bukus')->sum('stok'),
        'sedang_dipinjam'    => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
        'total_dikembalikan' => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
    ];

    return view('buku.index', compact('bukus', 'kategoris', 'stats'));
}

    public function create()
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.create', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        Buku::create($validated);

        return redirect()->route('buku.index')
            ->with('success', 'Buku berhasil ditambahkan!');
    }

    public function show(Buku $buku)
    {
        $buku->load('kategori');
        return view('buku.show', compact('buku'));
    }

    public function edit(Buku $buku)
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get();
        return view('buku.edit', compact('buku', 'kategoris'));
    }

    public function update(Request $request, Buku $buku)
    {
        $validated = $request->validate($this->rules($buku));
        $buku->update($validated);

        return redirect()->route('buku.index')
            ->with('success', 'Data buku berhasil diperbarui!');
    }

    public function destroy(Buku $buku)
    {
        $buku->delete();

        return redirect()->route('buku.index')
            ->with('success', 'Buku berhasil dihapus!');
    }

    private function rules(?Buku $buku = null): array
    {
        return [
            'isbn' => [
                'required', 'string', 'max:20',
                Rule::unique('bukus', 'isbn')->ignore($buku?->id),
            ],
            'judul'        => 'required|string|min:5|max:200',
            'penulis'      => 'required|string|max:150',
            'penerbit'     => 'required|string|max:150',
            'tahun_terbit' => 'required|integer|between:1900,' . date('Y'),
            'kategori_id'  => 'required|exists:kategoris,id',
            'stok'         => 'required|integer|min:0',
            'sinopsis'     => 'nullable|string',
        ];
    }
}