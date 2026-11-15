# PRD: PBI-08 — Melihat Jadwal Pengangkutan Sampah

| Item | Keterangan |
|---|---|
| Produk | PilahKu — Platform Pemilahan Sampah Universitas Telkom (SDG 12) |
| PBI | PBI-08 Melihat Jadwal Pengangkutan Sampah |
| Kebutuhan Fungsional | FR-08 (data bersumber dari FR-07 / PBI-07) |
| Aktor | Pengguna / Civitas Academica |
| Sprint | 2 (Week 7–8) |
| PIC | Zahra Denisa Salsabila (102022400022) |
| Status | Draft |

---

## 1. Ringkasan

Pengguna dapat melihat jadwal pengangkutan sampah aktif di kawasan Universitas Telkom: hari, jam buang, jam angkut, area/titik, kategori, dan status pengangkutan. Halaman ini bersifat **baca saja** (read-only) dan mengambil data yang dikelola admin lewat PBI-07. Jadwal ini membantu pengguna membuang sampah sesuai kategori pada waktu yang tepat.

## 2. Latar Belakang dan Masalah

Pada proses As-Is tidak ada informasi jadwal bagi pengguna, sehingga:
- Pengguna membuang sampah tanpa tahu jadwal kategorinya.
- Sampah tercampur dan petugas harus memilah ulang secara manual.

PBI-08 menyelesaikan sisi **penyajian informasi** kepada pengguna. Pengingat otomatis (PBI-09) dan validasi kesesuaian jadwal pada aktivitas (PBI-13) memakai data yang sama.

## 3. Tujuan

1. Pengguna mengetahui jadwal pengangkutan hari ini dan satu minggu ke depan dalam satu halaman.
2. Pengguna dapat menyaring jadwal berdasarkan tanggal, lokasi, dan kategori.
3. Hanya jadwal yang masih berlaku yang ditampilkan.

**Non-Tujuan:** pengelolaan jadwal (PBI-07), pengiriman pengingat (PBI-09), pelacakan petugas/armada real-time, dan pencatatan pengangkutan oleh petugas.

## 4. Pengguna dan Hak Akses

| Peran | Akses |
|---|---|
| Pengguna / Civitas | Melihat jadwal (baca saja) |
| Admin | Mengelola data jadwal lewat PBI-07; halaman ini hanya melihat |

Halaman PBI-08 tidak memerlukan login admin. Bila login pengguna dari PBI-01 belum tersedia, halaman dapat dibuka tanpa autentikasi sementara dan dipindahkan ke dalam middleware `auth` setelah PBI-01 merge.

## 5. User Stories

- **US-01** Sebagai pengguna, saya ingin melihat jadwal pengangkutan hari ini agar tahu kategori apa yang dibuang dan kapan diangkut.
- **US-02** Sebagai pengguna, saya ingin memilih tanggal dalam minggu ini agar bisa merencanakan pembuangan.
- **US-03** Sebagai pengguna, saya ingin menyaring jadwal berdasarkan lokasi dan kategori agar hanya melihat yang relevan.
- **US-04** Sebagai pengguna, saya ingin tahu apakah sampah di suatu titik sudah diangkut atau belum.
- **US-05** Sebagai pengguna, saya ingin mendapat pesan jelas bila jadwal belum tersedia.

## 6. Kebutuhan Fungsional

| ID | Kebutuhan | Prioritas |
|---|---|---|
| PBI08-F01 | Mengambil hanya jadwal berstatus `aktif` dan dalam periode berlaku | Must |
| PBI08-F02 | Kartu sorotan "Hari Ini": kategori hari ini, jam buang, jam angkut mulai, dan hitung mundur menuju pengangkutan | Must |
| PBI08-F03 | Strip "Minggu Ini" (Min–Sab); klik tanggal memuat jadwal tanggal tersebut; hari ini ditandai | Must |
| PBI08-F04 | Tabel jadwal per area untuk tanggal terpilih: Area, Titik, Kategori, Jam Buang, Angkut, Status | Must |
| PBI08-F05 | Status pengangkutan: Terjadwal / Sudah diangkut | Must |
| PBI08-F06 | Filter berdasarkan tanggal, lokasi, dan kategori | Should |
| PBI08-F07 | Pesan kosong "Jadwal belum tersedia" bila tidak ada hasil, dengan opsi memilih periode lain | Must |
| PBI08-F08 | Penanganan galat: pesan kesalahan bila data gagal dimuat dan opsi muat ulang | Must |
| PBI08-F09 | Tombol/link "Atur Pengingat" menuju halaman PBI-09 (boleh placeholder) | Could |
| PBI08-F10 | Tampilan responsif desktop dan mobile | Must |

### 6.1 Aturan Penentuan Jadwal pada Tanggal Tertentu

Sebuah jadwal tampil pada tanggal `T` bila:
1. `status = aktif`.
2. `berlaku_mulai ≤ T` dan (`berlaku_sampai` kosong atau `T ≤ berlaku_sampai`).
3. Pola cocok:
   - **Siklus N hari:** `(T − berlaku_mulai) mod N` sama dengan `hari_ke − 1`.
   - **Mingguan:** nama hari `T` sama dengan `hari` pada jadwal.
   - **Sekali:** `T = berlaku_mulai`.
4. Kategori dan lokasi terkait masih aktif.

> Catatan: aturan ini mengikuti kolom tambahan yang diusulkan di PRD PBI-07 (`pola`, `interval_hari`, `hari_ke`, `berlaku_mulai`, `berlaku_sampai`). Sesuaikan dengan kolom yang benar-benar ada di migration. Bila hanya kolom `hari` yang ada, gunakan pencocokan nama hari saja.

### 6.2 Status Pengangkutan

Untuk MVP, status **dihitung dari waktu** dan tidak disimpan:

| Kondisi (waktu WIB) | Status |
|---|---|
| Tanggal terpilih setelah hari ini | Terjadwal |
| Hari ini, sebelum jam angkut | Terjadwal |
| Hari ini, setelah jam angkut | Sudah diangkut |
| Tanggal terpilih sebelum hari ini | Sudah diangkut |

Pencatatan oleh petugas (log pengangkutan) berada di luar lingkup PBI-07 dan PBI-08.

## 7. Alur Pengguna

**Skenario normal:**
1. Pengguna membuka menu Jadwal Pengangkutan.
2. Sistem mengambil jadwal aktif.
3. Sistem menampilkan kartu "Hari Ini", strip minggu ini, dan tabel jadwal hari ini.
4. Pengguna memilih tanggal atau filter lokasi/kategori.
5. Sistem menampilkan jadwal sesuai pilihan.

**Skenario gagal:**
- Tidak ada jadwal pada periode/filter yang dipilih → sistem menampilkan "Jadwal belum tersedia"; pengguna memilih periode lain.
- Data gagal dimuat → sistem menampilkan pesan kesalahan dan tombol muat ulang.

## 8. Model Data (Dibaca)

Halaman ini hanya membaca tabel berikut, tanpa menulis:

| Tabel | Dipakai untuk |
|---|---|
| `jadwal_pengangkutan` | Pola, hari, jam, periode berlaku, status |
| `jadwal_lokasi` (pivot) | Titik/lokasi yang dilayani satu jadwal |
| `kategori_sampah` | Nama dan warna kategori |
| `lokasi_tempat_sampah` | Nama area dan kode titik |

Relasi Eloquent yang diharapkan: `JadwalPengangkutan` → `belongsTo(KategoriSampah)` dan `belongsToMany(LokasiTempatSampah, 'jadwal_lokasi')`.

Zona waktu aplikasi harus `Asia/Jakarta` (`config/app.php`) agar hitung mundur dan status akurat.

## 9. Rute dan API (Draft)

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/jadwal-pengangkutan` | Halaman jadwal (Blade) |
| GET | `/jadwal-pengangkutan?tanggal=YYYY-MM-DD&lokasi=&kategori=` | Halaman dengan filter |
| GET (opsional) | `/api/schedules/public?tanggal=&lokasi=&kategori=` | Data JSON jadwal aktif untuk pengguna |

Endpoint publik terpisah dari `/api/schedules` milik admin (PBI-07), sehingga akses baca pengguna tidak membuka akses tulis admin.

Contoh respons JSON:

```json
{
  "tanggal": "2026-11-07",
  "hari_ini": "Plastik",
  "pengangkutan_dalam": "03:42:00",
  "jadwal": [
    {
      "area": "Kantin TULT",
      "titik": ["A1", "A2", "A3", "A4"],
      "kategori": "Plastik",
      "jam_buang": "07:00-15:00",
      "jam_angkut": "16:00",
      "status": "Terjadwal"
    }
  ]
}
```

Kode respons: `200` sukses (termasuk daftar kosong), `422` parameter filter tidak valid, `500` galat server.

## 10. UI/UX

Mengacu pada Mockup no. 9 "Jadwal Pengangkutan Sampah":
- **Kartu sorotan hijau:** "Hari Ini • [hari, tanggal]", judul "Jadwal Pengangkutan", subteks "Buang pukul 07.00–15.00 • Pengangkutan mulai 16.00 di semua titik", dan kotak "Pengangkutan dalam 3 jam 42 mnt".
- **Strip "Minggu Ini":** tujuh kartu hari (Min–Sab) dengan tanggal dan jam angkut; hari ini disorot.
- **Tabel "Jadwal per Area – Hari Ini":** kolom Area, Titik, Kategori (titik warna), Jam Buang, Angkut, Status (badge kuning "Terjadwal" / hijau "Sudah diangkut").
- **Panel "Pengingat Kamu"** di kanan dengan link "Atur pengingat" (diisi PBI-09).
- Tombol "Atur Pengingat" di kanan atas.
- Warna kategori konsisten: Organik hijau, Plastik biru, Kertas kuning.
- Layar kecil: tabel menjadi daftar kartu; panel pengingat pindah ke bawah.

## 11. Kebutuhan Non-Fungsional Terkait

| ID | Target |
|---|---|
| NFR-01 Performance | Halaman dimuat ≤ 2 detik; gunakan eager loading agar tidak terjadi N+1 query |
| NFR-02 Availability | Halaman dapat diakses selama layanan tersedia |
| NFR-06 Usability | Status dan hitung mundur terbaca sekilas |
| NFR-07 Responsiveness | Berfungsi di desktop dan mobile |
| NFR-10 Data Integrity | Selalu mengikuti data aktif dari PBI-07; tidak menyimpan salinan |

## 12. Kriteria Penerimaan

1. **Given** ada jadwal aktif hari ini, **when** pengguna membuka menu, **then** kartu "Hari Ini" menampilkan kategori, jam buang, jam angkut, dan hitung mundur yang benar (WIB).
2. **Given** strip minggu ini tampil, **when** pengguna mengklik sebuah tanggal, **then** tabel menampilkan jadwal tanggal tersebut.
3. **Given** jadwal berstatus `nonaktif` atau di luar periode berlaku, **when** halaman dimuat, **then** jadwal itu tidak tampil.
4. **Given** pola siklus 3 hari dengan `hari_ke = 3`, **when** pengguna memilih tanggal, **then** jadwal hanya muncul pada tanggal yang sesuai aturan 6.1.
5. **Given** waktu sekarang sudah lewat jam angkut, **when** tabel hari ini dimuat, **then** status baris menjadi "Sudah diangkut".
6. **Given** filter lokasi atau kategori dipilih, **when** diterapkan, **then** hanya jadwal yang cocok yang tampil.
7. **Given** tidak ada jadwal untuk pilihan, **when** halaman dimuat, **then** pesan "Jadwal belum tersedia" tampil, bukan tabel kosong atau galat.
8. **Given** kategori atau lokasi dinonaktifkan di PBI-04/PBI-10, **when** halaman dimuat, **then** jadwal terkait tidak tampil.
9. **Given** layar lebar ≤ 480 px, **when** halaman dibuka, **then** tata letak tetap terbaca tanpa scroll horizontal.
10. **Given** jadwal diubah admin di PBI-07, **when** pengguna memuat ulang, **then** perubahan langsung terlihat.

## 13. Ketergantungan

| Ketergantungan | Keterangan |
|---|---|
| PBI-07 Kelola Jadwal | Sumber data jadwal (sudah dibuat) |
| PBI-04 Kategori Sampah | Nama dan warna kategori |
| PBI-10 Lokasi Tempat Sampah | Nama area dan kode titik |
| PBI-01 Autentikasi | Opsional; halaman dapat sementara terbuka tanpa login |
| PBI-09 Pengingat | Tujuan link "Atur Pengingat" (Sprint 3) |

Selama PBI-04 dan PBI-10 belum selesai, gunakan seeder kategori dan lokasi: Organik, Plastik, Kertas; serta titik Kantin TULT A1/A2, Gedung Selaru B1, Kantin Asrama C1, Pendopo D1, Parkiran TULT E1.

## 14. Rencana dan Tonggak

| Sprint | Minggu | Deliverable |
|---|---|---|
| 2 | 7–8 | Halaman jadwal pengguna: kartu hari ini, strip minggu, tabel, filter, status, pesan kosong |
| 3 | — | Integrasi tombol dan panel pengingat (PBI-09) |
| 5 | 14–15 | Integrasi, pengujian, perbaikan bug |

## 15. Risiko

| Risiko | Mitigasi |
|---|---|
| Kolom pola/periode belum ada di migration PBI-07 | Cocokkan struktur tabel sebelum menulis query |
| Data kosong setelah `migrate:fresh` | Seeder kategori, lokasi, dan jadwal |
| Zona waktu salah sehingga status dan hitung mundur meleset | Set `app.timezone = Asia/Jakarta`, uji di sekitar jam angkut |
| Query lambat karena relasi banyak titik | Eager loading (`with`) dan indeks pada `status`, `berlaku_mulai` |
| Jadwal bentrok dari PBI-07 membuat tampilan ganda | Kelompokkan per area/kategori dan hindari duplikasi baris |

## 16. Pertanyaan Terbuka

1. Apakah status "Sudah diangkut" cukup dihitung dari waktu, atau petugas perlu mencatatnya?
2. Apakah halaman jadwal terbuka untuk tamu (tanpa login) atau wajib login pengguna?
3. Kolom apa saja yang sebenarnya ada di tabel `jadwal_pengangkutan` dan `jadwal_lokasi`? Perlu dicocokkan dengan aturan 6.1.
4. Apakah jadwal ditampilkan per titik atau dikelompokkan per area (mis. A1–A4 dalam satu baris)?
5. Apakah filter kategori diperlukan pada MVP, atau cukup tanggal dan lokasi?

## 17. Pengujian

- **Unit:** aturan pencocokan pola (siklus, mingguan, sekali), penentuan status dari waktu, filter periode berlaku.
- **Feature (Laravel):** halaman memuat jadwal aktif saja, filter tanggal/lokasi/kategori, pesan kosong, respons JSON.
- **UI/E2E:** klik tanggal pada strip minggu, hitung mundur, tampilan mobile.
- **Integrasi:** jadwal yang dinonaktifkan atau diubah di PBI-07 langsung tercermin di PBI-08.
