<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:255'],
            'referred_by_doctor_id' => [
                'nullable',
                Rule::exists('doctors', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->where('is_active', true)),
            ],
        ];
    }
}
