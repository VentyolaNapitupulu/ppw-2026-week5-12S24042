<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * SIPUS-Del sebagai SERVICE CONSUMER:
 * memperkaya data buku lokal dengan metadata dari OpenLibrary REST API.
 */
class BukuReferensiController extends Controller
{
    use ApiResponse;

    private const OPENLIBRARY_URL = 'https://openlibrary.org/api/books';
    private const TIMEOUT_DETIK   = 8;
    private const CACHE_JAM       = 24;

    /**
     * GET /api/v1/bukus/{id}/referensi-eksternal  (publik)
     */
    public function show(string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan di katalog.",
                status: 404,
                errorSlug: 'book-not-found'
            );
        }

        $isbn     = $buku->isbn;
        $cacheKey = "openlibrary:isbn:{$isbn}";

        // 1. Panggil layanan eksternal dengan batas waktu (jangan biarkan klien menunggu selamanya)
        try {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => 'SIPUS-Del/1.0 (Praktikum PPW IT Del)'])
                ->connectTimeout(4)
                ->timeout(self::TIMEOUT_DETIK)
                ->get(self::OPENLIBRARY_URL, [
                    'bibkeys' => "ISBN:{$isbn}",
                    'format'  => 'json',
                    'jscmd'   => 'data',
                ]);
        } catch (ConnectionException $e) {
            // Timeout / DNS gagal / internet putus
            return $this->fallback(
                $buku,
                $cacheKey,
                status: 504,
                title: 'Gateway Timeout',
                detail: 'Tidak dapat terhubung ke OpenLibrary (melebihi ' . self::TIMEOUT_DETIK . ' detik atau koneksi terputus).',
                slug: 'external-service-timeout'
            );
        }

        // 2. Server eksternal merespons tetapi gagal (5xx / 4xx)
        if ($response->failed()) {
            return $this->fallback(
                $buku,
                $cacheKey,
                status: 502,
                title: 'Bad Gateway',
                detail: "OpenLibrary merespons dengan status {$response->status()}.",
                slug: 'external-service-error'
            );
        }

        // 3. OpenLibrary mengembalikan {} bila ISBN tidak dikenal
        $raw = $response->json("ISBN:{$isbn}");

        if (empty($raw)) {
            return $this->problemResponse(
                title: 'External Reference Not Found',
                detail: "ISBN {$isbn} tidak ditemukan di OpenLibrary.",
                status: 404,
                errorSlug: 'external-book-not-found'
            );
        }

        // 4. Ringkas data eksternal sesuai kebutuhan SIPUS-Del, lalu simpan ke cache
        $hasil = [
            'eksternal'    => $this->petakan($raw),
            'diambil_pada' => now()->toIso8601String(),
        ];
        Cache::put($cacheKey, $hasil, now()->addHours(self::CACHE_JAM));

        return $this->successResponse(
            data: $this->susunData($buku, $hasil['eksternal']),
            message: 'Metadata eksternal berhasil diambil dari OpenLibrary.',
            meta: [
                'sumber'       => 'openlibrary',
                'diambil_pada' => $hasil['diambil_pada'],
            ]
        );
    }

    /**
     * FALLBACK: bila layanan eksternal bermasalah, pakai salinan cache terakhir.
     * Bila cache kosong, kembalikan galat RFC 7807 (502/504).
     */
    private function fallback(Buku $buku, string $cacheKey, int $status, string $title, string $detail, string $slug): JsonResponse
    {
        $cache = Cache::get($cacheKey);

        if ($cache) {
            return $this->successResponse(
                data: $this->susunData($buku, $cache['eksternal']),
                message: 'OpenLibrary sedang tidak dapat dihubungi; menampilkan salinan cache terakhir.',
                meta: [
                    'sumber'       => 'cache',
                    'diambil_pada' => $cache['diambil_pada'],
                    'peringatan'   => $detail,
                ]
            );
        }

        return $this->problemResponse($title, $detail, $status, $slug);
    }

    private function petakan(array $raw): array
    {
        return [
            'judul'             => $raw['title'] ?? null,
            'subjudul'          => $raw['subtitle'] ?? null,
            'penulis'           => collect($raw['authors'] ?? [])->pluck('name')->values()->all(),
            'penerbit'          => collect($raw['publishers'] ?? [])->pluck('name')->values()->all(),
            'tanggal_terbit'    => $raw['publish_date'] ?? null,
            'jumlah_halaman'    => $raw['number_of_pages'] ?? null,
            'subjek'            => collect($raw['subjects'] ?? [])->pluck('name')->take(5)->values()->all(),
            'sampul'            => $raw['cover']['medium'] ?? null,
            'tautan_openlibrary'=> $raw['url'] ?? null,
        ];
    }

    private function susunData(Buku $buku, array $eksternal): array
    {
        return [
            'buku_lokal' => [
                'id'    => $buku->id,
                'isbn'  => $buku->isbn,
                'judul' => $buku->judul,
            ],
            'referensi_eksternal' => $eksternal,
            '_links' => [
                'buku' => [
                    'href'   => url("/api/v1/bukus/{$buku->id}"),
                    'method' => 'GET',
                ],
            ],
        ];
    }
}