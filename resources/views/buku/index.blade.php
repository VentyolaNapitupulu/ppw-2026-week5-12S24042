<x-layout title="Daftar Buku">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Daftar Buku</h1>
        <a href="{{ route('buku.create') }}" class="btn btn-del">+ Tambah Buku</a>
    </div>
        <div class="row g-3 mb-4">
        @foreach ([
            ['Total Judul Buku', $stats['total_judul']],
            ['Total Stok Tersedia', $stats['total_stok']],
            ['Sedang Dipinjam', $stats['sedang_dipinjam']],
            ['Total Dikembalikan', $stats['total_dikembalikan']],
        ] as [$label, $nilai])
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-3 fw-semibold">{{ number_format($nilai) }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('buku.index') }}" class="row g-2 mb-2">
        <div class="col-md-6">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Cari judul, penulis, atau ISBN">
        </div>
        <div class="col-md-4">
            <select name="kategori" class="form-select">
                <option value="">Semua kategori</option>
                @foreach ($kategoris as $k)
                    <option value="{{ $k->id }}" @selected(request('kategori') == $k->id)>{{ $k->nama_kategori }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-del">Cari</button>
        </div>
    </form>

    @if (session('last_search'))
        <p class="small text-muted mb-3">
            Pencarian terakhir:
            <a href="{{ route('buku.index', ['search' => session('last_search')]) }}">{{ session('last_search') }}</a>
        </p>
    @endif
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>ISBN</th><th>Judul</th><th>Penulis</th>
                        <th>Kategori</th><th>Stok</th><th>Dipinjam</th><th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td>{{ $bukus->firstItem() + $loop->index }}</td>
                            <td>{{ $buku->isbn }}</td>
                            <td>{{ $buku->judul }}</td>
                            <td>{{ $buku->penulis }}</td>
                            <td><span class="badge text-bg-secondary">{{ $buku->kategori->nama_kategori }}</span></td>
                            <td>{{ $buku->stok }}</td>
                            <td>{{ $buku->peminjamans_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('buku.show', $buku) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                                <a href="{{ route('buku.edit', $buku) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus buku ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data buku.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bukus->links() }}</div>
</x-layout>