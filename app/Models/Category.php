<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const PROPOSED = 'proposee';

    public const VALIDATED = 'validee';

    protected $fillable = ['name', 'description', 'status'];

    public function scopeValidated($query)
    {
        return $query->where('status', self::VALIDATED);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
