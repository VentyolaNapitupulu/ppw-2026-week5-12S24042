<x-layout title="Edit Buku">
    <h1 class="h4 mb-3">Edit Buku</h1>
    <div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('buku.update', $buku) }}" method="POST">
            @csrf
            @method('PUT')
            @include('buku._form')
            <button class="btn btn-del">Perbarui</button>
            <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div></div>
</x-layout>