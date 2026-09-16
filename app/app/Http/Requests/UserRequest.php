<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->input('role') === UserRole::ADMIN->value && ! $this->user()->isAdmin()) {
            return false;
        }

        return $this->route('user') ? $this->user()->can('update', $this->route('user')) : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return ['fullName' => ['required', 'string', 'max:255'], 'login' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('users', 'login')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', 'min:8', 'max:255'], 'role' => ['required', Rule::enum(UserRole::class)], 'isActive' => ['required', 'boolean']];
    }

    public function userData(): array
    {
        return collect($this->validated())->reject(fn ($v, $k) => $k === 'password' && ! $v)->mapWithKeys(fn ($v, $k) => [Str::snake($k) => $v])->all();
    }
}
