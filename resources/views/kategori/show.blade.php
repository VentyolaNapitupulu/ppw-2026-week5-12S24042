<x-layout title="Detail Kategori">
    <div class="card shadow-sm"><div class="card-body">
        <h1 class="h4">{{ $kategori->nama_kategori }} <small class="text-muted">({{ $kategori->kode_kategori }})</small></h1>
        <h2 class="h6 mt-4">Buku dalam kategori ini</h2>
        <ul class="list-group mb-3">
            @forelse ($kategori->bukus as $buku)
                <li class="list-group-item">
                    <a href="{{ route('buku.show', $buku) }}">{{ $buku->judul }}</a> — {{ $buku->penulis }}
                </li>
            @empty
                <li class="list-group-item text-muted">Belum ada buku di kategori ini.</li>
            @endforelse
        </ul>
        <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div></div>
</x-layout>