<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $katalog = [
            ['TKN', 'Teknologi Informasi', ['Pemrograman Web', 'Basis Data', 'Jaringan Komputer', 'Kecerdasan Buatan']],
            ['SNS', 'Sains', ['Fisika Dasar', 'Kimia Dasar', 'Biologi Dasar', 'Ilmu Lingkungan']],
            ['BSN', 'Bisnis dan Manajemen', ['Manajemen Proyek', 'Pemasaran Digital', 'Akuntansi Dasar', 'Kewirausahaan']],
            ['SSR', 'Sastra', ['Sastra Indonesia', 'Menulis Kreatif', 'Apresiasi Puisi', 'Telaah Prosa']],
            ['MTK', 'Matematika', ['Kalkulus', 'Statistika', 'Aljabar Linear', 'Matematika Diskrit']],
        ];

        $awalan = ['Pengantar', 'Dasar-Dasar', 'Panduan Praktis', 'Konsep Modern'];

        foreach ($katalog as [$kode, $nama, $topikList]) {
            $kategori = Kategori::create([
                'kode_kategori' => $kode,
                'nama_kategori' => $nama,
            ]);

            foreach ($topikList as $topik) {
                Buku::factory()->for($kategori)->create([
                    'judul'    => fake()->randomElement($awalan) . ' ' . $topik,
                    'sinopsis' => "Buku ini membahas $topik secara runtut, mulai dari konsep dasar hingga contoh penerapan yang dekat dengan kebutuhan mahasiswa.",
                ]);
            }
        }
    }
}