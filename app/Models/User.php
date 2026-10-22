<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Helper untuk mengecek permission pengguna
    public function hasAccess(string $moduleKey): bool
    {
        // 1. Cek apakah akun aktif
        if (!$this->is_active) {
            return false;
        }

        // 2. Jika tidak punya role, atur proteksi default
        if (!$this->role) {
            return false;
        }

        // 3. Full access roles bypass module grants
        if ($this->role->hasFullAccess()) {
            return true;
        }

        // 4. Panggil method pengecekan di Model Role jika ada
        if (method_exists($this->role, 'hasModule')) {
            return $this->role->hasModule($moduleKey);
        }

        return false;
    }

    // Alias method agar cocok dengan pemanggilan di routes/web.php
    public function hasModuleAccess(string $moduleKey): bool
    {
        return $this->hasAccess($moduleKey);
    }

    public function activityModuleName(): string
    {
        return 'User Management';
    }

    public function activitySubjectName(): string
    {
        return 'User';
    }

    public function activityLabel(): string
    {
        return $this->username ?: ($this->name ?: ('#' . $this->getKey()));
    }
}