<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware device.token
    }

    public function rules(): array
    {
        return [
            'token_qr' => ['required', 'string'],
            'kode_perangkat' => ['required', 'string', 'max:30'],
        ];
    }
}
