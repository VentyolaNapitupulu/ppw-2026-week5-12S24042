<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BukuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tersedia = $this->stok > 0;
        $self     = url("/api/v1/bukus/{$this->id}");

        // Kontrol Hypermedia (Richardson Maturity Model Level 3)
        $links = [
            'self'           => ['href' => $self, 'method' => 'GET'],
            'koleksi'        => ['href' => url('/api/v1/bukus'), 'method' => 'GET'],
            'update_full'    => ['href' => $self, 'method' => 'PUT'],
            'update_partial' => ['href' => $self, 'method' => 'PATCH'],
            'delete'         => ['href' => $self, 'method' => 'DELETE'],
            'referensi_eksternal' => ['href' => "{$self}/referensi-eksternal", 'method' => 'GET'],
        ];

        // HATEOAS dinamis: aksi pinjam HANYA muncul bila stok masih ada
        if ($tersedia) {
            $links['pinjam_buku'] = [
                'href'   => url('/api/v1/peminjamans'),
                'method' => 'POST',
                'body'   => ['buku_id' => $this->id],
            ];
        }

        return [
            'id'              => $this->id,
            'isbn'            => $this->isbn,
            'judul'           => $this->judul,
            'penulis'         => $this->penulis,
            'penerbit'        => $this->penerbit,
            'tahun_terbit'    => (int) $this->tahun_terbit,
            'stok'            => (int) $this->stok,
            'tersedia'        => $tersedia,
            'status_label'    => $tersedia ? 'TERSEDIA' : 'HABIS',
            'sinopsis'        => $this->sinopsis,
            'kategori'        => new KategoriResource($this->whenLoaded('kategori')),
            // Atribut terhitung: hanya muncul jika controller memanggil withCount()
            'total_dipinjam'  => $this->whenCounted('peminjamans'),
            'sedang_dipinjam' => $this->whenCounted('peminjaman_aktif'),
            // Riwayat hanya dimuat di endpoint detail (->load('peminjamans'))
            'riwayat_peminjaman' => PeminjamanResource::collection($this->whenLoaded('peminjamans')),
            'updated_at'      => $this->updated_at?->toIso8601String(),
            '_links'          => $links,
        ];
    }
}