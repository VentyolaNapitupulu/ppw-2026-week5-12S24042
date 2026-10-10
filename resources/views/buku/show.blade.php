<x-layout title="Detail Buku">
    <div class="card shadow-sm"><div class="card-body">
        <h1 class="h4">{{ $buku->judul }}</h1>
        <p class="text-muted">{{ $buku->penulis }} · {{ $buku->penerbit }} ({{ $buku->tahun_terbit }})</p>
        <dl class="row">
            <dt class="col-sm-3">ISBN</dt><dd class="col-sm-9">{{ $buku->isbn }}</dd>
            <dt class="col-sm-3">Kategori</dt><dd class="col-sm-9">{{ $buku->kategori->nama_kategori }}</dd>
            <dt class="col-sm-3">Stok</dt><dd class="col-sm-9">{{ $buku->stok }}</dd>
            <dt class="col-sm-3">Sinopsis</dt><dd class="col-sm-9">{{ $buku->sinopsis ?? '-' }}</dd>
        </dl>
        <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Kembali</a>
        <a href="{{ route('buku.edit', $buku) }}" class="btn btn-del">Edit</a>
    </div></div>
</x-layout>