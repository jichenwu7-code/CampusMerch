<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'operator_type',
        'target_type',
        'target_id',
        'action',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}