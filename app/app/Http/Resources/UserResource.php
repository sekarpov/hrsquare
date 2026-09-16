<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'fullName' => $this->full_name, 'login' => $this->login, 'role' => $this->role, 'isActive' => $this->is_active, 'mustChangePassword' => $this->must_change_password, 'createdAt' => $this->created_at?->toISOString()];
    }
}
