<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_LOGIN  = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_LOGIN_FAILED = 'LOGIN_FAILED';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_DOWNLOAD = 'DOWNLOAD';
    public const ACTION_ATTACH = 'ATTACH';
    public const ACTION_DETACH = 'DETACH';
    public const ACTION_PERMISSION_CHANGE = 'PERMISSION_CHANGE';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module_name',
        'subject_type',
        'subject_id',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Relasi ke pelaku aksi. Bisa null apabila akun user telah dihapus.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi polimorfik ke record yang terpengaruh (Document, Customer, dsb).
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Scope filter berdasarkan jenis aksi.
     */
    public function scopeAction(Builder $query, ?string $action): Builder
    {
        return $action ? $query->where('action', strtoupper($action)) : $query;
    }

    /**
     * Scope filter berdasarkan nama modul.
     */
    public function scopeModule(Builder $query, ?string $module): Builder
    {
        return $module ? $query->where('module_name', $module) : $query;
    }

    /**
     * Warna badge Bootstrap untuk tiap jenis aksi (dipakai di view).
     */
    public function getActionColorAttribute(): string
    {
        return match (strtoupper($this->action)) {
            self::ACTION_CREATE => 'success',
            self::ACTION_UPDATE => 'primary',
            self::ACTION_DELETE => 'danger',
            self::ACTION_LOGIN  => 'info',
            self::ACTION_LOGOUT => 'secondary',
            self::ACTION_LOGIN_FAILED => 'danger',
            self::ACTION_EXPORT => 'primary',
            self::ACTION_DOWNLOAD => 'secondary',
            self::ACTION_ATTACH => 'success',
            self::ACTION_DETACH => 'warning',
            self::ACTION_PERMISSION_CHANGE => 'warning',
            default             => 'dark',
        };
    }

    /**
     * Ikon FontAwesome untuk tiap jenis aksi (dipakai di view).
     */
    public function getActionIconAttribute(): string
    {
        return match (strtoupper($this->action)) {
            self::ACTION_CREATE => 'fa-circle-plus',
            self::ACTION_UPDATE => 'fa-pen-to-square',
            self::ACTION_DELETE => 'fa-trash-can',
            self::ACTION_LOGIN  => 'fa-right-to-bracket',
            self::ACTION_LOGOUT => 'fa-right-from-bracket',
            self::ACTION_LOGIN_FAILED => 'fa-shield-halved',
            self::ACTION_EXPORT => 'fa-file-export',
            self::ACTION_DOWNLOAD => 'fa-download',
            self::ACTION_ATTACH => 'fa-link',
            self::ACTION_DETACH => 'fa-link-slash',
            self::ACTION_PERMISSION_CHANGE => 'fa-user-shield',
            default             => 'fa-circle-info',
        };
    }
}
