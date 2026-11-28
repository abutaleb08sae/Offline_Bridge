<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'role',
        'status',
        'last_login_at',
        'allow_copy_paste',
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
            'last_login_at' => 'datetime',
            'allow_copy_paste' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return strtolower(trim((string) $this->role)) === 'admin';
    }

    public function problemBlocks(): HasMany
    {
        return $this->hasMany(ProblemBlock::class);
    }

    public function copyPastePermissions(): HasMany
    {
        return $this->hasMany(CopyPastePermission::class);
    }

    public function tabViolations(): HasMany
    {
        return $this->hasMany(TabViolation::class);
    }

    public function isTabBlocked(): bool
    {
        return ! $this->isAdmin()
            && $this->tabViolations()->where('is_blocked', true)->exists();
    }
}
