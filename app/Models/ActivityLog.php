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
            default             => 'fa-circle-info',
        };
    }
}
