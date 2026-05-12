<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $table = 'contact_submissions';

    protected $fillable = [
        'site_key',
        'name',
        'email',
        'company',
        'message',
        'budget_range',
        'source',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
