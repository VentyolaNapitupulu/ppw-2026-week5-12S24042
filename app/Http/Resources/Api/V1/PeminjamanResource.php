<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeminjamanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $masihDipinjam = $this->status === 'dipinjam';

        $links = [
            'self' => [
                'href'   => url("/api/v1/peminjamans/{$this->id}"),
                'method' => 'GET',
            ],
            'buku' => [
                'href'   => url("/api/v1/bukus/{$this->buku_id}"),
                'method' => 'GET',
            ],
        ];

        // HATEOAS dinamis: aksi kembalikan HANYA ditawarkan jika buku masih dipinjam
        if ($masihDipinjam) {
            $links['kembalikan_buku'] = [
                'href'   => url("/api/v1/peminjamans/{$this->id}/kembalikan"),
                'method' => 'PATCH',
            ];
        }

        return [
            'id'                  => $this->id,
            'kode_pinjam'         => $this->kode_pinjam,
            'nim_peminjam'        => $this->nim_peminjam,
            'nama_peminjam'       => $this->nama_peminjam,
            'tanggal_pinjam'      => $this->tanggal_pinjam?->toDateString(),
            'tanggal_jatuh_tempo' => $this->tanggal_jatuh_tempo?->toDateString(),
            'tanggal_kembali'     => $this->tanggal_kembali?->toDateString(),
            'status'              => $this->status,
            // Terlambat jika masih dipinjam dan hari jatuh tempo sudah lewat seluruhnya
            'terlambat'           => $masihDipinjam
                && (bool) $this->tanggal_jatuh_tempo?->copy()->endOfDay()->isPast(),
            // Detail buku hanya disertakan bila relasi sudah di-eager load
            'buku'                => $this->whenLoaded('buku', fn () => [
                'id'    => $this->buku->id,
                'isbn'  => $this->buku->isbn,
                'judul' => $this->buku->judul,
            ]),
            '_links'              => $links,
        ];
    }
}