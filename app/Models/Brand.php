<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'logo_image_id',
        'website',
        'description',
        'primary_color',
        'secondary_color',
        'notes',
    ];

    protected $with = [
        'logo.file'
    ];

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'logo_image_id');
    }

    // attribute to get logo file path?
}
