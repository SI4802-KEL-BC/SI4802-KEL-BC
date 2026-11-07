# PRD: PBI-07 — CRUD Jadwal Pengangkutan Sampah

| Item | Keterangan |
|---|---|
| Produk | PilahKu — Platform Pemilahan Sampah Universitas Telkom (SDG 12) |
| PBI | PBI-07 Mengelola Jadwal Pengangkutan Sampah |
| Kebutuhan Fungsional | FR-07 (terkait FR-08, FR-09) |
| Aktor | Admin |
| Sprint | 1 (Week 5–6) |
| PIC | Zahra Denisa Salsabila (102022400022) |
| Status | Draft |

---

## 1. Ringkasan

Admin membutuhkan fitur untuk menambah, melihat, mengubah, dan menonaktifkan jadwal pengangkutan sampah di kawasan Universitas Telkom. Jadwal ini menjadi data master yang ditampilkan kepada pengguna (PBI-08), dipakai sebagai dasar pengingat otomatis (PBI-09), dan menjadi acuan kesesuaian jadwal pada validasi aktivitas pemilahan (PBI-13/15) serta bonus konsistensi reward (PBI-16).

## 2. Latar Belakang dan Masalah

Pada proses As-Is, tidak ada informasi jadwal yang terdokumentasi sehingga:
- Pengguna membuang sampah tanpa mengetahui jadwal kategori, sehingga sampah tercampur.
- Informasi tidak seragam dan tidak terkini.
- Petugas harus memilah ulang secara manual.

PBI-07 menyelesaikan sisi **sumber data**: admin memiliki satu tempat resmi untuk mengelola jadwal.

## 3. Tujuan

1. Admin dapat mengelola jadwal pengangkutan per kategori, area/titik, hari, dan jam.
2. Data jadwal konsisten dan menjadi satu-satunya sumber untuk fitur pengguna lain.
3. Jadwal yang sudah tidak berlaku dinonaktifkan (bukan dihapus permanen) agar riwayat tetap terjaga.

**Non-Tujuan:** tampilan jadwal untuk pengguna (PBI-08), pengiriman pengingat (PBI-09), pelacakan armada real-time, dan penugasan petugas.

## 4. Pengguna dan Hak Akses

| Peran | Akses |
|---|---|
| Admin | Tambah, lihat, ubah, nonaktifkan/aktifkan jadwal |
| Pengguna/Civitas | Tidak ada akses ke halaman ini (hanya baca lewat PBI-08) |

Seluruh endpoint wajib autentikasi JWT dan otorisasi peran Admin (NFR-03, TR-06).

## 5. User Stories

- **US-01** Sebagai admin, saya ingin melihat daftar jadwal (filter Aktif/Nonaktif) agar tahu jadwal yang sedang berlaku.
- **US-02** Sebagai admin, saya ingin menambah jadwal baru (kategori, area/titik, pola pengulangan, jam buang, jam angkut) agar pengguna memiliki informasi terkini.
- **US-03** Sebagai admin, saya ingin mengubah jadwal agar perubahan operasional langsung tercermin.
- **US-04** Sebagai admin, saya ingin menonaktifkan jadwal yang tidak berlaku (mis. libur semester) tanpa menghapus datanya, dan dapat mengaktifkannya kembali.
- **US-05** Sebagai admin, saya ingin memilih apakah pengguna menerima pengingat untuk jadwal ini.

## 6. Kebutuhan Fungsional

| ID | Kebutuhan | Prioritas |
|---|---|---|
| PBI07-F01 | Menampilkan daftar jadwal dalam tabel: Kategori, Area, Pola, Jam Angkut, Status, Aksi | Must |
| PBI07-F02 | Tab filter Aktif / Nonaktif dengan jumlah data | Must |
| PBI07-F03 | Form tambah jadwal | Must |
| PBI07-F04 | Form ubah jadwal (panel edit di samping tabel) | Must |
| PBI07-F05 | Nonaktifkan dan aktifkan kembali jadwal (soft delete) | Must |
| PBI07-F06 | Validasi input sisi klien dan server dengan pesan kesalahan jelas | Must |
| PBI07-F07 | Notifikasi sukses: "Jadwal sudah berhasil disimpan" | Must |
| PBI07-F08 | Toggle "Kirim pengingat ke pengguna" per jadwal (dipakai PBI-09) | Should |
| PBI07-F09 | Pencarian/filter tabel berdasarkan kategori atau area | Could |
| PBI07-F10 | Peringatan bila jadwal baru bentrok dengan jadwal aktif lain (kategori + titik + waktu sama) | Should |

### 6.1 Field Formulir

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| Kategori | Dropdown (kategori_sampah aktif) | Ya | Harus ada dan berstatus aktif |
| Area / Titik | Multi-pilih (lokasi_tempat_sampah aktif) atau "Semua area" | Ya | Minimal satu |
| Pola Pengulangan | Pilihan: Siklus N hari / Mingguan / Sekali | Ya | |
| Hari ke- / Hari | Angka (siklus) atau hari (mingguan) | Ya | Sesuai pola |
| Mulai berlaku | Tanggal | Ya | Format tanggal valid |
| Berlaku sampai | Tanggal | Tidak | Tidak boleh sebelum Mulai berlaku |
| Jam buang | Rentang waktu (mulai–selesai) | Ya | Jam selesai > jam mulai |
| Jam angkut | Waktu | Ya | Harus ≥ jam selesai buang |
| Catatan | Teks maks. 255 | Tidak | |
| Kirim pengingat | Toggle | Tidak | Default aktif |

> Catatan: ERD pada proposal saat ini hanya memuat `hari`, `jam_angkut`, `catatan`, `status`. Mockup (no. 11) menampilkan atribut tambahan (pola, rentang jam buang, periode berlaku, flag pengingat). Lihat bagian 9 (Perubahan Data) dan 12 (Pertanyaan Terbuka).

## 7. Alur Pengguna

**Skenario normal (tambah/ubah):**
1. Admin membuka menu Jadwal Pengangkutan.
2. Sistem menampilkan daftar jadwal.
3. Admin memilih "Tambah Jadwal" atau "Edit".
4. Sistem menampilkan formulir.
5. Admin mengisi data.
6. Sistem memvalidasi.
7. Admin menekan "Simpan Jadwal".
8. Sistem menyimpan dan menampilkan notifikasi berhasil, daftar diperbarui.

**Skenario gagal:** data tidak valid (tanggal/waktu salah, field wajib kosong) → sistem menolak dan menampilkan pesan kesalahan per field; admin memperbaiki lalu validasi ulang.

**Nonaktifkan:** Admin klik "Nonaktif" → dialog konfirmasi → status menjadi Nonaktif, baris pindah ke tab Nonaktif, dan jadwal tidak tampil ke pengguna maupun memicu pengingat.

## 8. Aturan Bisnis

1. Jadwal tidak pernah dihapus permanen; hanya berstatus `aktif` / `nonaktif`.
2. Hanya jadwal `aktif` dan dalam periode berlaku yang tampil ke pengguna dan memicu pengingat.
3. Kategori atau lokasi yang dinonaktifkan tidak dapat dipilih pada jadwal baru. Jadwal lama yang merujuknya ditandai untuk ditinjau admin.
4. Perubahan jadwal aktif yang pengingatnya menyala memicu notifikasi "Perubahan jadwal" sesuai preferensi pengguna (PBI-09).
5. Jam angkut tidak boleh lebih awal dari akhir jam buang.
6. Hanya Admin yang dapat mengubah data jadwal.

## 9. Model Data

Berdasarkan ERD proposal, tabel `jadwal_pengangkutan`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_jadwal | INT, PK | |
| id_kategori | INT, FK → kategori_sampah | |
| id_lokasi | INT, FK → lokasi_tempat_sampah | Titik angkut |
| hari | VARCHAR(10) | Sudah ada di ERD |
| jam_angkut | TIME | Sudah ada di ERD |
| catatan | VARCHAR(255) | Sudah ada di ERD |
| status | VARCHAR(15) | `aktif` / `nonaktif` |

**Usulan penambahan kolom** (agar sesuai mockup):

| Kolom | Tipe | Keterangan |
|---|---|---|
| pola | VARCHAR(15) | `siklus` / `mingguan` / `sekali` |
| interval_hari | INT NULL | Untuk pola siklus (mis. 3) |
| hari_ke | INT NULL | Posisi kategori dalam siklus |
| jam_buang_mulai | TIME | |
| jam_buang_selesai | TIME | |
| berlaku_mulai | DATE | |
| berlaku_sampai | DATE NULL | |
| kirim_pengingat | BOOLEAN | Default true |

Relasi: `jadwal_pengangkutan` 1 : 0..N `pengingat_jadwal`; dimiliki satu `kategori_sampah` dan satu `lokasi_tempat_sampah`. Pilihan "Semua area" disimpan sebagai satu baris per lokasi aktif atau sebagai `id_lokasi` NULL (diputuskan tim, lihat bagian 12).

## 10. API (Draft)

Semua endpoint di bawah `/api/schedules`, format JSON, wajib JWT + peran Admin.

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/schedules?status=aktif\|nonaktif&kategori=&lokasi=` | Daftar jadwal |
| GET | `/api/schedules/:id` | Detail jadwal |
| POST | `/api/schedules` | Tambah jadwal |
| PUT | `/api/schedules/:id` | Ubah jadwal |
| PATCH | `/api/schedules/:id/status` | Aktifkan / nonaktifkan |

Contoh body POST:

```json
{
  "id_kategori": 3,
  "id_lokasi": [1, 2],
  "pola": "siklus",
  "interval_hari": 3,
  "hari_ke": 3,
  "berlaku_mulai": "2026-10-11",
  "berlaku_sampai": "2026-12-31",
  "jam_buang_mulai": "07:00",
  "jam_buang_selesai": "15:00",
  "jam_angkut": "16:00",
  "catatan": "",
  "kirim_pengingat": true
}
```

Kode respons: `200/201` sukses, `400` validasi gagal (berisi pesan per field), `401` belum login, `403` bukan admin, `404` tidak ditemukan, `409` jadwal bentrok.

## 11. UI/UX

Mengacu pada Mockup no. 11 "Admin Kelola Jadwal Pengangkutan":
- Sidebar panel admin, item "Jadwal Pengangkutan" aktif.
- Header: judul, deskripsi ("jadwal tidak berlaku dinonaktifkan, bukan dihapus"), tombol hijau **+ Tambah Jadwal**.
- Tab **Aktif (n)** / **Nonaktif (n)**.
- Tabel: Kategori (titik warna), Area, Pola, Angkut, Status (badge), Aksi (Edit, Nonaktifkan / Aktifkan).
- Panel **Edit Jadwal** di kanan: Kategori, Area/Titik (chip), Pola Pengulangan (segmented control), Hari ke-, Mulai berlaku, Jam buang, Jam angkut, Berlaku sampai, toggle Kirim pengingat, tombol Batal dan Simpan Jadwal.
- Warna kategori konsisten dengan panduan: Organik hijau, Plastik biru, Kertas kuning.
- Responsif desktop dan mobile (NFR-07); panel edit menjadi halaman/drawer penuh di layar kecil.

## 12. Kebutuhan Non-Fungsional Terkait

| ID | Target |
|---|---|
| NFR-01 Performance | Daftar jadwal dimuat dalam ≤ 2 detik untuk ≤ 500 data |
| NFR-03 Security | JWT dan otorisasi peran pada semua endpoint |
| NFR-05/10 Reliability & Integrity | Simpan atomik; FK menjaga konsistensi kategori dan lokasi |
| NFR-06 Usability | Pesan kesalahan jelas di dekat field |
| NFR-07 Responsiveness | Berfungsi di desktop dan mobile |

## 13. Kriteria Penerimaan

1. **Given** admin login, **when** membuka menu Jadwal Pengangkutan, **then** daftar jadwal tampil dengan tab Aktif/Nonaktif dan jumlah yang benar.
2. **Given** formulir terisi valid, **when** menekan Simpan Jadwal, **then** data tersimpan, notifikasi sukses muncul, dan jadwal tampil di daftar.
3. **Given** jam angkut lebih awal dari jam buang selesai, atau tanggal berakhir sebelum tanggal mulai, **when** menyimpan, **then** sistem menolak dengan pesan kesalahan pada field terkait.
4. **Given** field wajib kosong, **when** menyimpan, **then** penyimpanan ditolak dan data tidak berubah.
5. **Given** jadwal aktif, **when** admin menonaktifkan dan mengonfirmasi, **then** status menjadi Nonaktif, pindah ke tab Nonaktif, tidak tampil di PBI-08, dan tidak memicu pengingat.
6. **Given** jadwal nonaktif, **when** admin mengaktifkan, **then** jadwal kembali aktif dan tampil ke pengguna.
7. **Given** pengguna biasa atau tanpa token, **when** memanggil endpoint, **then** respons `403` / `401`.
8. **Given** jadwal baru bentrok dengan jadwal aktif (kategori + titik + waktu sama), **when** menyimpan, **then** sistem memberi peringatan/penolakan `409`.
9. **Given** kategori atau lokasi nonaktif, **when** admin membuka dropdown, **then** opsi tersebut tidak tersedia.

## 14. Ketergantungan

| Ketergantungan | Keterangan |
|---|---|
| PBI-04 Kategori Sampah | Sumber dropdown kategori (Sprint 1, paralel) |
| PBI-10 Lokasi Tempat Sampah | Sumber pilihan area/titik (Sprint 1, paralel) |
| PBI-01 Autentikasi | Login dan peran Admin |
| PBI-08, PBI-09 | Konsumen data jadwal (Sprint 2–3) |

Karena PBI-04 dan PBI-10 dikerjakan paralel di Sprint 1, gunakan data seed/mock kategori dan lokasi sampai keduanya selesai.

## 15. Rencana dan Tonggak

| Sprint | Minggu | Deliverable |
|---|---|---|
| 1 | 5–6 | CRUD jadwal (API + UI admin), validasi, soft delete |
| 3 | — | Integrasi pengingat (PBI-09) |
| 5 | 14–15 | Integrasi, pengujian, perbaikan bug |

## 16. Risiko

| Risiko | Mitigasi |
|---|---|
| Skema ERD belum mencakup atribut mockup | Sepakati migrasi kolom tambahan di awal sprint |
| Ketergantungan data kategori/lokasi | Seed data sementara |
| Jadwal bentrok / tidak konsisten | Validasi bentrok di server |
| Zona waktu pengingat | Simpan jam dalam WIB (UTC+7) |

## 17. Pertanyaan Terbuka

1. Apakah "Semua area" disimpan sebagai satu baris per lokasi atau `id_lokasi` NULL?
2. Apakah pola "Siklus 3 hari" (Organik → Kertas → Plastik) perlu tabel siklus terpisah agar rotasi kategori terjamin konsisten?
3. Bentrok jadwal: ditolak (`409`) atau hanya peringatan?
4. Apakah perlu log audit perubahan jadwal (siapa dan kapan mengubah)?
5. Apakah jadwal "Sekali" (libur/insidental) dibutuhkan pada MVP?

## 18. Pengujian

- **Unit:** validasi tanggal/jam, aturan bentrok, perubahan status.
- **API:** otorisasi per peran, kode respons, filter status.
- **UI/E2E:** alur tambah → ubah → nonaktifkan → aktifkan, pesan kesalahan, tampilan mobile.
- **Integrasi:** jadwal aktif muncul di PBI-08; jadwal nonaktif tidak.
