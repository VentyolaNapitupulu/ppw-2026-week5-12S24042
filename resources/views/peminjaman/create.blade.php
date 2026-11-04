<x-layout title="Peminjaman Baru">
    <h1 class="h4 mb-3">Peminjaman Baru</h1>
    <div class="card shadow-sm"><div class="card-body">
        <form action="{{ route('peminjaman.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Buku</label>
                <select name="buku_id" class="form-select @error('buku_id') is-invalid @enderror">
                    <option value="">-- Pilih buku --</option>
                    @foreach ($bukus as $b)
                        <option value="{{ $b->id }}" @selected(old('buku_id') == $b->id)>
                            {{ $b->judul }} (stok: {{ $b->stok }})
                        </option>
                    @endforeach
                </select>
                @error('buku_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">NIM Peminjam</label>
                    <input type="text" name="nim_peminjam" value="{{ old('nim_peminjam') }}"
                           class="form-control @error('nim_peminjam') is-invalid @enderror">
                    @error('nim_peminjam') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Nama Peminjam</label>
                    <input type="text" name="nama_peminjam" value="{{ old('nama_peminjam') }}"
                           class="form-control @error('nama_peminjam') is-invalid @enderror">
                    @error('nama_peminjam') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Lama Pinjam</label>
                    <select name="lama_hari" class="form-select @error('lama_hari') is-invalid @enderror">
                        @foreach ([3, 7, 14] as $hari)
                            <option value="{{ $hari }}" @selected(old('lama_hari', 7) == $hari)>{{ $hari }} hari</option>
                        @endforeach
                    </select>
                    @error('lama_hari') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <button class="btn btn-del">Catat Peminjaman</button>
            <a href="{{ route('peminjaman.index') }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div></div>
</x-layout>