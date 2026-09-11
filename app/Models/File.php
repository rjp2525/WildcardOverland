<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'original_filename',
        'original_extension',
        'mime',
        'hash',
        'type', // content, static
        'size', // bytes, create getter for mb/gb etc
        'stored_path',
        'disk',
    ];

    public function image(): HasOne
    {
        return $this->hasOne(Image::class, 'file_id');
    }
}
