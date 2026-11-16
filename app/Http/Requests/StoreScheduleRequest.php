<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kategori' => ['required', 'integer', 'exists:kategori_sampah,id_kategori,status,aktif'],
            'id_lokasi' => $this->boolean('semua_area') ? ['exclude'] : ['required', 'array', 'min:1'],
            'id_lokasi.*' => $this->boolean('semua_area')
                ? ['exclude']
                : ['integer', 'distinct', 'exists:lokasi_tempat_sampah,id_lokasi,status,aktif'],
            'semua_area' => ['sometimes', 'boolean'],
            'pola' => ['required', 'in:siklus,mingguan,sekali'],
            'interval_hari' => ['required_if:pola,siklus', 'nullable', 'integer', 'min:1', 'max:365'],
            'hari_ke' => ['required_if:pola,siklus', 'nullable', 'integer', 'min:1'],
            'hari' => ['required_if:pola,mingguan', 'nullable', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'berlaku_mulai' => ['required', 'date_format:Y-m-d'],
            'berlaku_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:berlaku_mulai'],
            'jam_buang_mulai' => ['required', 'date_format:H:i'],
            'jam_buang_selesai' => ['required', 'date_format:H:i', 'after:jam_buang_mulai'],
            'jam_angkut' => ['required', 'date_format:H:i', 'after_or_equal:jam_buang_selesai'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'kirim_pengingat' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kategori.exists' => 'Kategori tidak tersedia atau sudah nonaktif.',
            'id_lokasi.*.exists' => 'Lokasi tidak tersedia atau sudah nonaktif.',
            'id_lokasi.required_unless' => 'Pilih setidaknya satu lokasi atau pilih Semua area.',
            'hari_ke.required_if' => 'Hari ke wajib diisi untuk pola siklus.',
            'hari_ke.min' => 'Hari ke harus berada dalam rentang siklus.',
            'hari.required_if' => 'Pilih hari untuk pola mingguan.',
            'berlaku_sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.',
            'jam_buang_selesai.after' => 'Jam selesai buang harus setelah jam mulai buang.',
            'jam_angkut.after_or_equal' => 'Jam angkut tidak boleh lebih awal dari jam selesai buang.',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $this->input('pola') === 'siklus'
                && is_numeric($this->input('hari_ke'))
                && is_numeric($this->input('interval_hari'))
                && (int) $this->input('hari_ke') > (int) $this->input('interval_hari')
            ) {
                $validator->errors()->add('hari_ke', 'Hari ke tidak boleh melebihi interval siklus.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Data jadwal tidak valid.',
                'errors' => $validator->errors(),
            ], 400));
        }

        parent::failedValidation($validator);
    }
}
