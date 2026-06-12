<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function primaryRoleLabel(): string
    {
        $role = $this->roles->first();

        if ($role === null) {
            return 'Sin rol';
        }

        $key = $role->name;

        return config("stockflow_permissions.roles.{$key}.label", ucfirst($key));
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'owner_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function optionsForSelect(): array
    {
        return static::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (self $u) => ['id' => $u->id, 'name' => $u->name])
            ->all();
    }
}
