<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(UserIndexRequest $request)
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection($this->query($request)->paginate($request->integer('perPage', 20)));
    }

    public function managers(UserIndexRequest $request)
    {
        return UserResource::collection($this->query($request, UserRole::MANAGER)->paginate($request->integer('perPage', 30)));
    }

    public function recruiters(UserIndexRequest $request)
    {
        return UserResource::collection($this->query($request, UserRole::RECRUITER)->paginate($request->integer('perPage', 30)));
    }

    private function query(UserIndexRequest $request, ?UserRole $role = null)
    {
        $q = User::query()->orderBy('full_name')->orderBy('id');
        if ($role) {
            $q->where('role', $role)->where('is_active', true);
        } elseif ($request->filled('role')) {
            $q->where('role', $request->validated('role'));
        }
        if ($request->filled('search')) {
            $q->where(fn ($q) => $q->where('full_name', 'ilike', '%'.$request->validated('search').'%')->orWhere('login', 'ilike', '%'.$request->validated('search').'%'));
        }

        return $q;
    }

    public function store(UserRequest $request, UserService $service): UserResource
    {
        return new UserResource($service->save($request->userData()));
    }

    public function update(UserRequest $request, User $user, UserService $service): UserResource
    {
        return new UserResource($service->save($request->userData(), $user));
    }

    public function destroy(User $user, UserService $service)
    {
        Gate::authorize('delete', $user);
        $service->delete($user);

        return response()->noContent();
    }
}
