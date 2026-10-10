<x-layout title="Edit Kategori">
    <h1 class="h4 mb-3">Edit Kategori</h1>
    <div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('kategori.update', $kategori) }}" method="POST">
            @csrf
            @method('PUT')
            @include('kategori._form')
            <button class="btn btn-del">Perbarui</button>
            <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div></div>
</x-layout>