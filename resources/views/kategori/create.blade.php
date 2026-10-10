<x-layout title="Tambah Kategori">
    <h1 class="h4 mb-3">Tambah Kategori</h1>
    <div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('kategori.store') }}" method="POST">
            @csrf
            @include('kategori._form')
            <button class="btn btn-del">Simpan</button>
            <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div></div>
</x-layout>