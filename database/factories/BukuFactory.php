<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buku>
 */
class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $awalan = ['Pengantar', 'Dasar-Dasar', 'Panduan Praktis', 'Konsep Modern', 'Analisis dan Perancangan'];
        $topik = [
            'Pemrograman Web', 'Basis Data', 'Kecerdasan Buatan', 'Manajemen Proyek',
            'Statistika', 'Sistem Informasi', 'Kalkulus', 'Jaringan Komputer',
            'Interaksi Manusia dan Komputer',
        ];
        $penerbit = [
            'Gramedia Pustaka Utama', 'Informatika Bandung', 'Andi Publisher',
            'Erlangga', 'Deepublish', 'Mizan',
        ];

        return [
            'isbn'         => fake()->unique()->isbn13(),
            'judul'        => fake()->randomElement($awalan) . ' ' . fake()->randomElement($topik),
            'penulis'      => fake('id_ID')->name(),
            'penerbit'     => fake()->randomElement($penerbit),
            'tahun_terbit' => fake()->numberBetween(2005, 2025),
            'kategori_id'  => Kategori::factory(),
            'stok'         => fake()->numberBetween(0, 30),
            'sinopsis'     => fake('id_ID')->paragraph(3),
        ];
    }
}