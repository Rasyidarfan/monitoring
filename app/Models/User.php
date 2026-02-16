<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sso_user_id',
        'nip_9',
        'nip_18',
        'sso_roles',
        'last_sso_sync_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'sso_roles' => 'array',
            'last_sso_sync_at' => 'datetime',
        ];
    }

    /**
     * Check if user has specific SSO role
     */
    public function hasSsoRole(string $role): bool
    {
        return in_array($role, $this->sso_roles ?? []);
    }

    /**
     * Check if user has any of the given SSO roles
     */
    public function hasAnySsoRole(array $roles): bool
    {
        return !empty(array_intersect($roles, $this->sso_roles ?? []));
    }

    /**
     * Get formatted NIP
     */
    public function getFormattedNipAttribute(): string
    {
        return $this->nip_18 ?? $this->nip_9 ?? '-';
    }

    /**
     * Upload histories uploaded by this user
     */
    public function uploadHistories()
    {
        return $this->hasMany(UploadHistory::class, 'uploaded_by');
    }
}
