<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Category;
use App\Models\Location;
use App\Models\Schedule;
use App\Services\ScheduleManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'in:aktif,nonaktif'],
            'kategori' => ['sometimes', 'integer'],
            'lokasi' => ['sometimes', 'integer'],
            'q' => ['sometimes', 'string', 'max:100'],
        ]);

        $schedules = Schedule::query()
            ->with(['category', 'locations'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['kategori'] ?? null, fn ($query, $category) => $query->where('id_kategori', $category))
            ->when($filters['lokasi'] ?? null, fn ($query, $location) => $query->whereHas(
                'locations',
                fn ($query) => $query->where('lokasi_tempat_sampah.id_lokasi', $location),
            ))
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('category', fn ($query) => $query->where('nama_kategori', 'like', "%{$search}%"))
                        ->orWhereHas('locations', fn ($query) => $query->where('nama_lokasi', 'like', "%{$search}%"));
                });
            })
            ->latest('id_jadwal')
            ->paginate(15);

        return response()->json([
            'data' => ScheduleResource::collection($schedules),
            'meta' => [
                'aktif' => Schedule::where('status', 'aktif')->count(),
                'nonaktif' => Schedule::where('status', 'nonaktif')->count(),
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
            ],
        ]);
    }

    public function show(Schedule $schedule): ScheduleResource
    {
        return new ScheduleResource($schedule->load(['category', 'locations']));
    }

    public function store(StoreScheduleRequest $request, ScheduleManager $manager): JsonResponse
    {
        $data = $request->validated();

        if ($manager->hasConflict($data)) {
            return response()->json(['message' => 'Jadwal bentrok dengan jadwal aktif lainnya.'], 409);
        }

        $schedule = $manager->save($data);

        return (new ScheduleResource($schedule))
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreScheduleRequest $request, Schedule $schedule, ScheduleManager $manager): JsonResponse|ScheduleResource
    {
        $data = $request->validated();

        if ($schedule->status === 'aktif' && $manager->hasConflict($data, $schedule)) {
            return response()->json(['message' => 'Jadwal bentrok dengan jadwal aktif lainnya.'], 409);
        }

        return new ScheduleResource($manager->save($data, $schedule));
    }

    public function updateStatus(Request $request, Schedule $schedule, ScheduleManager $manager): JsonResponse|ScheduleResource
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
            return response()->json(['message' => 'Jadwal bentrok dengan jadwal aktif lainnya.'], 409);
        }

        $schedule->update(['status' => $data['status']]);

        return new ScheduleResource($schedule->load(['category', 'locations']));
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'kategori' => Category::where('status', 'aktif')
                ->orderBy('nama_kategori')
                ->get(['id_kategori', 'nama_kategori', 'warna_label'])
                ->map(fn (Category $category): array => [
                    'id_kategori' => $category->id_kategori,
                    'nama' => $category->nama,
                    'warna' => $category->warna,
                ]),
            'lokasi' => Location::where('status', 'aktif')
                ->orderBy('nama_lokasi')
                ->get(['id_lokasi', 'nama_lokasi'])
                ->map(fn (Location $location): array => [
                    'id_lokasi' => $location->id_lokasi,
                    'nama' => $location->nama,
                ]),
        ]);
    }
}
