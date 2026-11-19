<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware device.token
    }

    public function rules(): array
    {
        return [
            'kode_perangkat' => ['required', 'string', 'max:30'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
