@php
    $fields = ['isbn' => 'ISBN', 'judul' => 'Judul', 'penulis' => 'Penulis', 'penerbit' => 'Penerbit'];
@endphp

@foreach ($fields as $name => $label)
    <div class="mb-3">
        <label class="form-label">{{ $label }}</label>
        <input type="text" name="{{ $name }}"
               class="form-control @error($name) is-invalid @enderror"
               value="{{ old($name, $buku->$name ?? '') }}">
        @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@endforeach

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Tahun Terbit</label>
        <input type="number" name="tahun_terbit"
               class="form-control @error('tahun_terbit') is-invalid @enderror"
               value="{{ old('tahun_terbit', $buku->tahun_terbit ?? '') }}">
        @error('tahun_terbit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Stok</label>
        <input type="number" name="stok"
               class="form-control @error('stok') is-invalid @enderror"
               value="{{ old('stok', $buku->stok ?? 0) }}">
        @error('stok') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Kategori</label>
    <select name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror">
        <option value="">-- Pilih kategori --</option>
        @foreach ($kategoris as $k)
            <option value="{{ $k->id }}"
                @selected(old('kategori_id', $buku->kategori_id ?? '') == $k->id)>
                {{ $k->nama_kategori }}
            </option>
        @endforeach
    </select>
    @error('kategori_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Sinopsis</label>
    <textarea name="sinopsis" rows="4"
              class="form-control @error('sinopsis') is-invalid @enderror">{{ old('sinopsis', $buku->sinopsis ?? '') }}</textarea>
    @error('sinopsis') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>