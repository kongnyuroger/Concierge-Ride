<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Permission names for API/frontend consumption. The owner role has no
     * permissions explicitly assigned (AppServiceProvider's Gate::before
     * bypasses every check for them instead — see /docs/adr/0011), so this
     * reports the full permission set for owners rather than an empty one.
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        if ($this->hasRole(UserRole::Owner->value)) {
            return collect(Permission::cases())->pluck('value');
        }

        return $this->getAllPermissions()->pluck('name');
    }

    /**
     * The shape exposed to the frontend — from GET /api/user and from the
     * login response, so nav gating has role/permissions immediately after
     * sign-in without a second request. One place, so the two can't drift.
     *
     * @return array<string, mixed>
     */
    public function toAuthPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->getRoleNames()->first(),
            'permissions' => $this->permissionNames(),
        ];
    }
}
