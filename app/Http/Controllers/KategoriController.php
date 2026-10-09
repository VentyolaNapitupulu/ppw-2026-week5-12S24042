<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::withCount('bukus')->orderBy('nama_kategori')->paginate(10);
        return view('kategori.index', compact('kategoris'));
    }

    public function create()
    {
        return view('kategori.create');
    }

    public function store(Request $request)
    {
        Kategori::create($request->validate($this->rules()));

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function show(Kategori $kategori)
    {
        $kategori->load('bukus');
        return view('kategori.show', compact('kategori'));
    }

    public function edit(Kategori $kategori)
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $kategori->update($request->validate($this->rules($kategori)));

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete(); // buku di kategori ini ikut terhapus (cascadeOnDelete)

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil dihapus!');
    }

    private function rules(?Kategori $kategori = null): array
    {
        return [
            'kode_kategori' => [
                'required', 'string', 'max:10',
                Rule::unique('kategoris', 'kode_kategori')->ignore($kategori?->id),
            ],
            'nama_kategori' => 'required|string|min:3|max:100',
        ];
    }
}