<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const PROPOSED = 'proposee';

    public const VALIDATED = 'validee';

    public const ORIGIN_ADMIN = 'admin';

    public const ORIGIN_PRODUCER = 'producteur';

    protected $fillable = ['name', 'description', 'status', 'origin', 'user_id'];

    public function scopeValidated($query)
    {
        return $query->where('status', self::VALIDATED);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
