<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ---------- Week 5: 5 kategori x 4 buku ----------
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

        // ---------- Week 6: akun pustakawan uji ----------
        // Password di-hash otomatis oleh cast 'hashed' pada model User.
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            ['name' => 'Pustakawan Del', 'password' => 'password123']
        );

        // ---------- Week 6: buku dengan stok 0 dan stok 1 ----------
        $tkn = Kategori::where('kode_kategori', 'TKN')->firstOrFail();

        $bukuHabis = Buku::factory()->for($tkn)->create([
            'judul'    => 'Rekayasa Perangkat Lunak Modern',
            'stok'     => 0, // satu-satunya eksemplar sedang dipinjam (lihat sampel 1)
            'sinopsis' => 'Buku ini membahas siklus pengembangan perangkat lunak, dari kebutuhan hingga pemeliharaan.',
        ]);

        Buku::factory()->for($tkn)->create([
            'judul'    => 'Keamanan Aplikasi Web',
            'stok'     => 1, // eksemplar terakhir, dipakai untuk menguji penolakan peminjaman kedua
            'sinopsis' => 'Buku ini membahas CSRF, XSS, SQL injection, dan pengelolaan sesi pada aplikasi web.',
        ]);

        // ---------- Week 6: sampel peminjaman (stok dijaga konsisten) ----------
        [$bukuA, $bukuB] = Buku::orderBy('id')->take(2)->get()->all();
        $bukuA->update(['stok' => 9]);  // 10 eksemplar, 1 sedang dipinjam
        $bukuB->update(['stok' => 10]); // semua eksemplar tersedia

        // Sampel 1: dipinjam, belum jatuh tempo (buku stok 0)
        Peminjaman::create([
            'kode_pinjam'         => 'PJM-SEED-001',
            'buku_id'             => $bukuHabis->id,
            'nim_peminjam'        => '12S24001',
            'nama_peminjam'       => 'Andreas Simanjuntak',
            'tanggal_pinjam'      => now()->subDays(2)->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(5)->toDateString(),
            'status'              => 'dipinjam',
        ]);

        // Sampel 2: dipinjam dan sudah terlambat
        Peminjaman::create([
            'kode_pinjam'         => 'PJM-SEED-002',
            'buku_id'             => $bukuA->id,
            'nim_peminjam'        => '12S24002',
            'nama_peminjam'       => 'Br. Sitorus',
            'tanggal_pinjam'      => now()->subDays(14)->toDateString(),
            'tanggal_jatuh_tempo' => now()->subDays(7)->toDateString(),
            'status'              => 'dipinjam',
        ]);

        // Sampel 3: sudah dikembalikan
        Peminjaman::create([
            'kode_pinjam'         => 'PJM-SEED-003',
            'buku_id'             => $bukuB->id,
            'nim_peminjam'        => '12S24003',
            'nama_peminjam'       => 'Marito Napitupulu',
            'tanggal_pinjam'      => now()->subDays(10)->toDateString(),
            'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
            'tanggal_kembali'     => now()->subDays(4)->toDateString(),
            'status'              => 'dikembalikan',
        ]);
    }
}