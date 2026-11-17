<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schedule extends Model
{
    protected $table = 'jadwal_pengangkutan';

    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_kategori',
        'pola',
        'interval_hari',
        'hari_ke',
        'hari',
        'berlaku_mulai',
        'berlaku_sampai',
        'jam_buang_mulai',
        'jam_buang_selesai',
        'jam_angkut',
        'catatan',
        'kirim_pengingat',
        'semua_area',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date:Y-m-d',
            'berlaku_sampai' => 'date:Y-m-d',
            'kirim_pengingat' => 'boolean',
            'semua_area' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_kategori', 'id_kategori');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'jadwal_lokasi', 'id_jadwal', 'id_lokasi');
    }

    public static function lihatJadwal(?string $tanggal = null, ?int $lokasiId = null): Collection
    {
        $selectedDate = CarbonImmutable::parse($tanggal ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta')
            ->startOfDay();
        $dateString = $selectedDate->toDateString();

        $schedules = static::query()
            ->with([
                'category',
                'locations' => fn ($query) => $query->where('lokasi_tempat_sampah.status', 'aktif'),
            ])
            ->where('status', 'aktif')
            ->whereDate('berlaku_mulai', '<=', $dateString)
            ->where(fn ($query) => $query
                ->whereNull('berlaku_sampai')
                ->orWhereDate('berlaku_sampai', '>=', $dateString))
            ->whereHas('category', fn ($query) => $query->where('status', 'aktif'))
            ->where(fn ($query) => $query
                ->where('semua_area', true)
                ->orWhereHas('locations', function ($query) use ($lokasiId): void {
                    $query->where('lokasi_tempat_sampah.status', 'aktif')
                        ->when($lokasiId !== null, fn ($query) => $query->where(
                            'lokasi_tempat_sampah.id_lokasi',
                            $lokasiId,
                        ));
                }))
            ->orderBy('jam_angkut')
            ->get();

        return $schedules
            ->filter(function (Schedule $schedule) use ($selectedDate): bool {
                return match ($schedule->pola) {
                    'siklus' => static::matchesCycle($schedule, $selectedDate),
                    'mingguan' => $schedule->hari === [
                        'minggu',
                        'senin',
                        'selasa',
                        'rabu',
                        'kamis',
                        'jumat',
                        'sabtu',
                    ][$selectedDate->dayOfWeek],
                    'sekali' => $schedule->berlaku_mulai?->format('Y-m-d') === $selectedDate->toDateString(),
                    default => false,
                };
            })
            ->values();
    }

    private static function matchesCycle(Schedule $schedule, CarbonImmutable $selectedDate): bool
    {
        if (
            $schedule->interval_hari === null
            || $schedule->interval_hari < 1
            || $schedule->hari_ke === null
            || $schedule->hari_ke < 1
            || $schedule->hari_ke > $schedule->interval_hari
            || $schedule->berlaku_mulai === null
        ) {
            return false;
        }

        $startDate = CarbonImmutable::parse($schedule->berlaku_mulai->format('Y-m-d'), 'Asia/Jakarta');
        $daysSinceStart = (int) $startDate->diffInDays($selectedDate);

        return $daysSinceStart % $schedule->interval_hari === $schedule->hari_ke - 1;
    }
}
