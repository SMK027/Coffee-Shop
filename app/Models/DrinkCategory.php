<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrinkCategory extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function drinks(): HasMany
    {
        return $this->hasMany(Drink::class, 'category_id')->orderBy('sort_order');
    }

    public function availableDrinks(): HasMany
    {
        return $this->drinks()->where('available', true);
    }
}
