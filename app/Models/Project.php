<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use LogsActivity;

    protected $fillable = [
        'project_name',
        'client_id',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Document::class)->where('type', 'INVOICE');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function directExpenses(): HasMany
    {
        return $this->transactions()->where('type', 'expense');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function activityModuleName(): string
    {
        return 'Project Management';
    }

    public function activitySubjectName(): string
    {
        return 'Project';
    }

    public function activityLabel(): string
    {
        return $this->project_name;
    }

    public static function activeForSelection(?int $includeId = null)
    {
        return static::query()
            ->with('client')
            ->active()
            ->when($includeId, function (Builder $query) use ($includeId) {
                $query->orWhereKey($includeId);
            })
            ->orderBy('project_name')
            ->get();
    }
}
