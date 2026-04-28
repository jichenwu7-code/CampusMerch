<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id', 'admin_name', 'operation_type', 
        'operation_content', 'ip', 'operation_time'
    ];

    protected $casts = [
        'operation_time' => 'datetime'
    ];
}