<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Schedule;
use App\Services\ScheduleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchedulePageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'in:aktif,nonaktif'],
            'q' => ['sometimes', 'string', 'max:100'],
        ]);
        $status = $filters['status'] ?? 'aktif';

        $schedules = Schedule::query()
            ->with(['category', 'locations'])
            ->where('status', $status)
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('category', fn ($query) => $query->where('nama_kategori', 'like', "%{$search}%"))
                        ->orWhereHas('locations', fn ($query) => $query->where('nama_lokasi', 'like', "%{$search}%"));
                });
            })
            ->latest('id_jadwal')
            ->paginate(10)
            ->withQueryString();

        return view('admin.schedules.index', [
            'schedules' => $schedules,
            'categories' => Category::where('status', 'aktif')->orderBy('nama_kategori')->get(),
            'locations' => Location::where('status', 'aktif')->orderBy('nama_lokasi')->get(),
            'activeCount' => Schedule::where('status', 'aktif')->count(),
            'inactiveCount' => Schedule::where('status', 'nonaktif')->count(),
            'status' => $status,
        ]);
    }

    public function store(StoreScheduleRequest $request, ScheduleManager $manager): RedirectResponse
    {
        $data = $request->validated();

        if ($manager->hasConflict($data)) {
            return back()
                ->withInput()
                ->withErrors(['jam_angkut' => 'Jadwal bentrok dengan jadwal aktif lainnya.']);
        }

        $manager->save($data);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Jadwal sudah berhasil disimpan');
    }

    public function update(StoreScheduleRequest $request, Schedule $schedule, ScheduleManager $manager): RedirectResponse
    {
        $data = $request->validated();

        if ($schedule->status === 'aktif' && $manager->hasConflict($data, $schedule)) {
            return back()
                ->withInput()
                ->with('edit_schedule', $schedule->id_jadwal)
                ->withErrors(['jam_angkut' => 'Jadwal bentrok dengan jadwal aktif lainnya.']);
        }

        $manager->save($data, $schedule);

        return redirect()->route('admin.schedules.index', ['status' => $schedule->status])
            ->with('success', 'Jadwal sudah berhasil disimpan');
    }

    public function updateStatus(Request $request, Schedule $schedule, ScheduleManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        if (
            $data['status'] === 'aktif'
            && $schedule->status !== 'aktif'
            && $manager->hasConflict([
                ...$schedule->attributesToArray(),
                'id_lokasi' => $schedule->locations()->pluck('lokasi_tempat_sampah.id_lokasi')->all(),
            ], $schedule)
        ) {
            return back()->withErrors(['status' => 'Jadwal bentrok dengan jadwal aktif lainnya.']);
        }

        $schedule->update(['status' => $data['status']]);

        return redirect()->route('admin.schedules.index', ['status' => $data['status']])
            ->with('success', $data['status'] === 'aktif'
                ? 'Jadwal berhasil diaktifkan.'
                : 'Jadwal berhasil dinonaktifkan.');
    }
}
