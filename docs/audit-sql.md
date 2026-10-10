# Audit Kueri SQL: Bukti Eager Loading

Halaman yang diuji: `/buku` (10 buku per halaman, 20 buku di database).
Log diambil dari `storage/logs/laravel.log` lewat listener `DB::listen`
di `AppServiceProvider`.

## SEBELUM: tanpa eager loading (N+1 problem)

Kode di `BukuController@index`:

```php
$bukus = Buku::latest()->paginate(10);
```

Log:

```
[SQL AUDIT] (0.29 ms) select count(*) as aggregate from "bukus" | bindings: []
[SQL AUDIT] (0.46 ms) select * from "bukus" order by "created_at" desc limit 10 offset 0 | bindings: []
[SQL AUDIT] (0.23 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [1]
[SQL AUDIT] (0.31 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [1]
[SQL AUDIT] (0.23 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [1]
[SQL AUDIT] (0.22 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [1]
[SQL AUDIT] (0.31 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [2]
[SQL AUDIT] (0.26 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [2]
[SQL AUDIT] (0.25 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [2]
[SQL AUDIT] (0.25 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [2]
[SQL AUDIT] (0.31 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [3]
[SQL AUDIT] (0.26 ms) select * from "kategoris" where "kategoris"."id" = ? limit 1 | bindings: [3]
```

Total: **12 query** (1 count + 1 daftar buku + 10 query kategori).

## SESUDAH: dengan eager loading

Kode di `BukuController@index`:

```php
$bukus = Buku::with('kategori')->latest()->paginate(10);
```

Log:

```
[SQL AUDIT] (0.31 ms) select count(*) as aggregate from "bukus" | bindings: []
[SQL AUDIT] (0.30 ms) select * from "bukus" order by "created_at" desc limit 10 offset 0 | bindings: []
[SQL AUDIT] (0.26 ms) select * from "kategoris" where "kategoris"."id" in (1, 2, 3) | bindings: []
```

Total: **3 query** (1 count + 1 daftar buku + 1 query kategori).

## Bukti tambahan: withCount di halaman kategori

`KategoriController@index` memakai `withCount('bukus')`:

```
[SQL AUDIT] (0.34 ms) select count(*) as aggregate from "kategoris" | bindings: []
[SQL AUDIT] (0.48 ms) select "kategoris".*, (select count(*) from "bukus" where "kategoris"."id" = "bukus"."kategori_id") as "bukus_count" from "kategoris" order by "nama_kategori" asc limit 10 offset 0 | bindings: []
```

Jumlah buku semua kategori dihitung dalam satu query (subquery), bukan satu query per kategori.

## Kesimpulan

Dari hasil audit, halaman `/buku` tanpa eager loading menjalankan 12 query,
sedangkan dengan `with('kategori')` hanya 3 query. Selisih ini terjadi karena
N+1 problem: Laravel mengambil 10 buku dengan satu query, lalu setiap kali
view memanggil `$buku->kategori->nama_kategori` di dalam loop, Eloquent
menjalankan query baru ke tabel `kategoris` untuk baris tersebut (lazy
loading). Pada log SEBELUM terlihat kategori yang sama (id 1, 2, dan 3)
diambil berulang kali karena lazy loading tidak mengenali duplikat.

Dengan eager loading, semua kategori yang dibutuhkan diambil sekaligus lewat
satu query `where id in (1, 2, 3)`, jadi jumlah query tidak lagi bertambah
mengikuti jumlah baris data. Dampaknya makin terasa pada data yang lebih
besar: jika satu halaman menampilkan 50 buku, versi tanpa eager loading
menjalankan 52 query, sedangkan versi eager loading tetap 3 query.

Query `count(*)` pada kedua log berasal dari `paginate()` yang membutuhkan
total data untuk menghitung jumlah halaman, sehingga hasilnya 3 query dan
bukan 2 seperti yang tertulis di modul. Selain itu, halaman kategori memakai
`withCount('bukus')` yang menghitung jumlah buku per kategori lewat satu
subquery, sehingga pola N+1 juga dihindari di sana.