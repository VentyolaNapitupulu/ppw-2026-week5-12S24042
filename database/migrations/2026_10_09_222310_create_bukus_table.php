<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bukus', function (Blueprint $table) {
            $table->id();
            $table->string('isbn', 20)->unique();
            $table->string('judul', 200);
            $table->string('penulis', 150);
            $table->string('penerbit', 150);
            $table->unsignedSmallInteger('tahun_terbit');
            $table->foreignId('kategori_id')->index()->constrained('kategoris')->cascadeOnDelete();
            $table->unsignedInteger('stok')->default(0);
            $table->text('sinopsis')->nullable();
            $table->timestamps();

            $table->index('judul');
            $table->index('tahun_terbit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bukus');
    }
};