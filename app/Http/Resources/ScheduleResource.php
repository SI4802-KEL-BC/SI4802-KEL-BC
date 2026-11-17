<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_jadwal' => $this->id_jadwal,
            'kategori' => [
                'id_kategori' => $this->category->id_kategori,
                'nama' => $this->category->nama,
                'warna' => $this->category->warna,
            ],
            'id_kategori' => $this->id_kategori,
            'lokasi' => $this->semua_area
                ? []
                : $this->locations->map(fn ($location): array => [
                    'id_lokasi' => $location->id_lokasi,
                    'nama' => $location->nama,
                ]),
            'semua_area' => $this->semua_area,
            'pola' => $this->pola,
            'interval_hari' => $this->interval_hari,
            'hari_ke' => $this->hari_ke,
            'hari' => $this->hari,
            'berlaku_mulai' => $this->berlaku_mulai?->format('Y-m-d'),
            'berlaku_sampai' => $this->berlaku_sampai?->format('Y-m-d'),
            'jam_buang_mulai' => substr($this->jam_buang_mulai, 0, 5),
            'jam_buang_selesai' => substr($this->jam_buang_selesai, 0, 5),
            'jam_angkut' => substr($this->jam_angkut, 0, 5),
            'catatan' => $this->catatan,
            'kirim_pengingat' => $this->kirim_pengingat,
            'status' => $this->status,
        ];
    }
}
