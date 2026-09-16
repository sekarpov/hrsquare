<?php

namespace App\Http\Requests;

use App\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'currentPassword' => [Rule::requiredIf(! $this->user()->must_change_password), 'nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed:passwordConfirmation', Rule::notIn([UserService::DEFAULT_PASSWORD])],
            'passwordConfirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'currentPassword.required' => 'Введите текущий пароль.',
            'password.required' => 'Придумайте новый пароль.',
            'password.min' => 'Пароль должен содержать не менее 8 символов.',
            'password.max' => 'Пароль должен содержать не более 255 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
            'password.not_in' => 'Выберите личный пароль, отличный от временного.',
            'passwordConfirmation.required' => 'Повторите новый пароль.',
        ];
    }
}
