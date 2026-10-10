<x-layout title="Daftar Kategori">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Daftar Kategori</h1>
        <a href="{{ route('kategori.create') }}" class="btn btn-del">+ Tambah Kategori</a>
    </div>

    <div class="card shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Kode</th><th>Nama</th><th>Jumlah Buku</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($kategoris as $kategori)
                    <tr>
                        <td>{{ $kategori->kode_kategori }}</td>
                        <td>{{ $kategori->nama_kategori }}</td>
                        <td>{{ $kategori->bukus_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('kategori.show', $kategori) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('kategori.destroy', $kategori) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Menghapus kategori juga menghapus SEMUA buku di dalamnya. Lanjut?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $kategoris->links() }}</div>
</x-layout>