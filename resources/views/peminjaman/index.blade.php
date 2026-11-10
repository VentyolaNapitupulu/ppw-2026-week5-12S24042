<x-layout title="Peminjaman Buku">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Peminjaman Buku</h1>
        <a href="{{ route('peminjaman.create') }}" class="btn btn-del">+ Peminjaman Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th><th>Buku</th><th>Peminjam</th>
                        <th>Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $p)
                        <tr>
                            <td>{{ $p->kode_pinjam }}</td>
                            <td>{{ $p->buku->judul }}</td>
                            <td>{{ $p->nama_peminjam }}<br><small class="text-muted">{{ $p->nim_peminjam }}</small></td>
                            <td>{{ $p->tanggal_pinjam->format('d M Y') }}</td>
                            <td>{{ $p->tanggal_jatuh_tempo->format('d M Y') }}</td>
                            <td>
                                @if ($p->status === 'dikembalikan')
                                    <span class="badge text-bg-success">Dikembalikan {{ $p->tanggal_kembali->format('d M Y') }}</span>
                                @elseif ($p->tanggal_jatuh_tempo->lt(today()))
                                    <span class="badge text-bg-danger">Terlambat</span>
                                @else
                                    <span class="badge text-bg-warning">Dipinjam</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($p->status === 'dipinjam')
                                    <form action="{{ route('peminjaman.kembalikan', $p) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Tandai buku ini sudah dikembalikan?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary">Kembalikan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi peminjaman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $peminjamans->links() }}</div>
</x-layout>