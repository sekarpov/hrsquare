<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): UserResource
    {
        if (! Auth::attempt([...$request->validated(), 'is_active' => true])) {
            throw ValidationException::withMessages(['login' => ['Неверный логин или пароль.']]);
        }
        $request->session()->regenerate();
        $request->session()->put('auth_version', $request->user()->auth_version);

        return new UserResource($request->user());
    }

    public function changePassword(ChangePasswordRequest $request): UserResource
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->auth_version === (int) $request->session()->get('auth_version', 0), 401);
            if (! $user->must_change_password && ! Hash::check((string) $request->input('currentPassword'), $user->password)) {
                throw ValidationException::withMessages(['currentPassword' => ['Текущий пароль указан неверно.']]);
            }
            if (Hash::check($request->validated('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => ['Новый пароль должен отличаться от текущего.']]);
            }
            $user->password = $request->validated('password');
            $user->must_change_password = false;
            $user->auth_version++;
            $user->save();

            return $user;
        });
        Auth::setUser($user);
        $request->session()->regenerate();
        $request->session()->put('auth_version', $user->auth_version);

        return new UserResource($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
