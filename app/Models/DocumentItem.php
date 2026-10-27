<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'description',
        'item_type',
        'notes',
        'qty',
        'unit',
        'price',
        'subtotal',
    ];

    // Relasi balik: 1 Item milik 1 Document
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}