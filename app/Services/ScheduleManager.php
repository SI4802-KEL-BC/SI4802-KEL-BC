<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class ScheduleManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function hasConflict(array $data, ?Schedule $except = null): bool
    {
        $query = Schedule::query()
            ->where('status', 'aktif')
            ->where('id_kategori', $data['id_kategori'])
            ->where('jam_buang_mulai', $data['jam_buang_mulai'])
            ->where('jam_buang_selesai', $data['jam_buang_selesai'])
            ->where('jam_angkut', $data['jam_angkut']);

        if ($except !== null) {
            $query->where('id_jadwal', '!=', $except->id_jadwal);
        }

        if ((bool) ($data['semua_area'] ?? false)) {
            return $query->exists();
        }

        $locationIds = $data['id_lokasi'] ?? [];

        return $query->where(function ($locations) use ($locationIds): void {
            $locations
                ->where('semua_area', true)
                ->orWhereHas('locations', fn ($query) => $query->whereIn('lokasi_tempat_sampah.id_lokasi', $locationIds));
        })->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?Schedule $schedule = null): Schedule
    {
        return DB::transaction(function () use ($data, $schedule): Schedule {
            $locationIds = ($data['semua_area'] ?? false) ? [] : $data['id_lokasi'];
            unset($data['id_lokasi']);
            $data['semua_area'] = (bool) ($data['semua_area'] ?? false);
            $data['kirim_pengingat'] = (bool) ($data['kirim_pengingat'] ?? false);
            $data['status'] ??= 'aktif';

            if ($data['pola'] !== 'siklus') {
                $data['interval_hari'] = null;
                $data['hari_ke'] = null;
            }

            if ($data['pola'] !== 'mingguan') {
                $data['hari'] = null;
            }

            $schedule ??= new Schedule;
            $schedule->fill($data);
            $schedule->save();
            $schedule->locations()->sync($locationIds);

            return $schedule->load(['category', 'locations']);
        });
    }
}
