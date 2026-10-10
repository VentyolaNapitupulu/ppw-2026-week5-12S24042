<x-layout title="Tambah Buku">
    <h1 class="h4 mb-3">Tambah Buku</h1>
    <div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('buku.store') }}" method="POST">
            @csrf
            @include('buku._form')
            <button class="btn btn-del">Simpan</button>
            <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div></div>
</x-layout>