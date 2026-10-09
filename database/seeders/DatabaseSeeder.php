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
        $data = [
            ['TKN', 'Teknologi Informasi'],
            ['SNS', 'Sains'],
            ['BSN', 'Bisnis dan Manajemen'],
            ['SSR', 'Sastra'],
            ['MTK', 'Matematika'],
        ];

        $kategoris = collect($data)->map(fn ($d) => Kategori::create([
            'kode_kategori' => $d[0],
            'nama_kategori' => $d[1],
        ]));

        Buku::factory(20)->recycle($kategoris)->create();
    }
}