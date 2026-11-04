<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PeminjamanController extends Controller
{
    public function index()
    {
        $peminjamans = Peminjaman::with('buku')->latest()->paginate(10);
        return view('peminjaman.index', compact('peminjamans'));
    }

    public function create()
    {
        $bukus = Buku::orderBy('judul')->get(['id', 'judul', 'stok']);
        return view('peminjaman.create', compact('bukus'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'buku_id'       => 'required|exists:bukus,id',
            'nim_peminjam'  => 'required|string|min:6|max:15',
            'nama_peminjam' => 'required|string|min:3|max:150',
            'lama_hari'     => 'required|integer|in:3,7,14',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // 1. Kunci baris buku: transaksi lain yang mengincar buku ini harus antre
                $buku = Buku::where('id', $validated['buku_id'])->lockForUpdate()->firstOrFail();

                // 2. Cek stok SETELAH terkunci, bukan sebelumnya
                if ($buku->stok < 1) {
                    throw new \RuntimeException("Stok buku \"{$buku->judul}\" habis.");
                }

                // 3. Cegah NIM yang sama meminjam buku yang sama sebelum mengembalikannya
                $nim = strtoupper(trim($validated['nim_peminjam']));
                $masihDipinjam = Peminjaman::where('buku_id', $buku->id)
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($masihDipinjam) {
                    throw new \RuntimeException("NIM {$nim} masih meminjam buku ini dan belum mengembalikannya.");
                }

                // 4. Dua penulisan yang harus sukses bersama
                $buku->decrement('stok');

                Peminjaman::create([
                    'kode_pinjam'         => 'PJM-' . now()->format('ymd') . '-' . Str::upper(Str::random(5)),
                    'buku_id'             => $buku->id,
                    'nim_peminjam'        => $nim,
                    'nama_peminjam'       => $validated['nama_peminjam'],
                    'tanggal_pinjam'      => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays((int) $validated['lama_hari'])->toDateString(),
                    'status'              => 'dipinjam',
                ]);
            }, 3);

            return redirect()->route('peminjaman.index')
                ->with('success', 'Peminjaman berhasil dicatat dan stok buku diperbarui.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION][pinjam]: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Terjadi kegagalan database. Peminjaman dibatalkan, stok tidak berubah.');
        }
    }

    public function kembalikan(Peminjaman $peminjaman)
    {
        try {
            DB::transaction(function () use ($peminjaman) {
                // Urutan kunci sama dengan store(): buku dulu, baru peminjaman
                $buku = Buku::where('id', $peminjaman->buku_id)->lockForUpdate()->firstOrFail();
                $pinjam = Peminjaman::where('id', $peminjaman->id)->lockForUpdate()->firstOrFail();

                // Dibaca ulang SETELAH terkunci: cegah klik ganda menambah stok dua kali
                if ($pinjam->status === 'dikembalikan') {
                    throw new \RuntimeException('Buku ini sudah dikembalikan sebelumnya.');
                }

                $pinjam->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                $buku->increment('stok');
            }, 3);

            return back()->with('success', 'Pengembalian berhasil dicatat dan stok buku bertambah.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION][kembali]: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kegagalan database. Pengembalian dibatalkan.');
        }
    }
}