<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    protected $fillable = ['title', 'template', 'language', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
