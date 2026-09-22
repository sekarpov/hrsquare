<?php

namespace App\Http\Requests;

use App\Models\Candidate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Candidate::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', Rule::unique('cities', 'name')]];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Введите название города.',
            'name.max' => 'Название города не должно превышать 255 символов.',
            'name.unique' => 'Такой город уже есть в справочнике.',
        ];
    }
}
