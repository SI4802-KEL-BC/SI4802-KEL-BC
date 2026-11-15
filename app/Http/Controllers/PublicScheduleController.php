<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PublicScheduleController extends Controller
{
    public function index(Request $request): View|Response
    {
        try {
            return view('schedules.public', $this->pageData($request));
        } catch (QueryException $exception) {
            report($exception);

            return response()->view('schedules.load-error', status: 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pageData(Request $request): array
    {
        $validator = Validator::make($request->query(), [
            'tanggal' => ['sometimes', 'date_format:Y-m-d'],
            'lokasi' => ['sometimes', 'nullable', 'integer', 'exists:lokasi_tempat_sampah,id_lokasi,status,aktif'],
        ], [
            'tanggal.date_format' => 'Format tanggal tidak valid. Pilih tanggal lain.',
            'lokasi.exists' => 'Lokasi tidak tersedia. Pilih lokasi aktif.',
        ]);
        $filtersAreValid = $validator->passes();
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $selectedDate = $filtersAreValid && $request->query('tanggal')
            ? CarbonImmutable::createFromFormat('!Y-m-d', $request->query('tanggal'), 'Asia/Jakarta')
            : $today;
        $locationId = $filtersAreValid && $request->filled('lokasi')
            ? (int) $request->query('lokasi')
            : null;

        $selectedSchedules = Schedule::lihatJadwal($selectedDate->toDateString(), $locationId);
        $todaySchedules = Schedule::lihatJadwal($today->toDateString(), $locationId);
        $now = CarbonImmutable::now('Asia/Jakarta');
        $nextScheduleToday = $todaySchedules
            ->filter(fn (Schedule $schedule): bool => $schedule->jam_angkut >= $now->format('H:i:s'))
            ->sortBy('jam_angkut')
            ->first();
        $highlightSchedule = $nextScheduleToday ?? $todaySchedules->first();
        $countdownSeconds = null;

        if ($nextScheduleToday !== null) {
            $haulTime = CarbonImmutable::parse(
                $today->toDateString().' '.$nextScheduleToday->jam_angkut,
                'Asia/Jakarta',
            );
            $countdownSeconds = max(0, (int) $now->diffInSeconds($haulTime, false));
        }

        $weekStart = $today->startOfWeek(CarbonImmutable::SUNDAY);
        $weekdayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $weekDays = collect(range(0, 6))->map(function (int $offset) use (
            $weekStart,
            $weekdayNames,
            $locationId,
            $selectedDate,
            $today,
        ): array {
            $date = $weekStart->addDays($offset);
            $daySchedules = Schedule::lihatJadwal($date->toDateString(), $locationId);

            return [
                'date' => $date,
                'label' => $weekdayNames[$offset],
                'haulTimes' => $daySchedules
                    ->pluck('jam_angkut')
                    ->map(fn (string $haulTime): string => str_replace(':', '.', substr($haulTime, 0, 5)))
                    ->unique()
                    ->values()
                    ->all(),
                'selected' => $date->isSameDay($selectedDate),
                'today' => $date->isSameDay($today),
            ];
        });

        $rows = $selectedSchedules->flatMap(function (Schedule $schedule) use (
            $locationId,
            $selectedDate,
            $today,
        ): array {
            $locations = $schedule->semua_area
                ? collect()
                : $schedule->locations->when(
                    $locationId !== null,
                    fn ($locations) => $locations->where('id_lokasi', $locationId),
                );

            if ($locations->isEmpty()) {
                $locations = collect([null]);
            }

            return $locations->map(function (?Location $location) use ($schedule, $selectedDate, $today): array {
                $area = $location?->nama ?? 'Semua area';
                $point = 'Semua titik';

                if ($location !== null && preg_match('/^(.*)\s+([A-Z]\d+)$/u', $location->nama, $matches) === 1) {
                    $area = $matches[1];
                    $point = $matches[2];
                } elseif ($location !== null) {
                    $point = $location->nama;
                }

                $status = 'Terjadwal';

                if ($selectedDate->lessThan($today) || (
                    $selectedDate->isSameDay($today)
                    && $schedule->jam_angkut < CarbonImmutable::now('Asia/Jakarta')->format('H:i:s')
                )) {
                    $status = 'Sudah diangkut';
                }

                return [
                    'schedule' => $schedule,
                    'area' => $area,
                    'point' => $point,
                    'status' => $status,
                ];
            })->all();
        })->values();

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        $weekdayLong = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return [
            'selectedDate' => $selectedDate,
            'today' => $today,
            'selectedLocationId' => $locationId,
            'selectedLocation' => $locationId === null ? null : Location::find($locationId),
            'locations' => Location::query()->where('status', 'aktif')->orderBy('nama_lokasi')->get(),
            'selectedSchedules' => $selectedSchedules,
            'todaySchedules' => $todaySchedules,
            'highlightSchedule' => $highlightSchedule,
            'countdownSeconds' => $countdownSeconds,
            'weekDays' => $weekDays,
            'rows' => $rows,
            'filterError' => $filtersAreValid ? null : $validator->errors()->first(),
            'monthNames' => $monthNames,
            'weekdayLong' => $weekdayLong,
        ];
    }
}
