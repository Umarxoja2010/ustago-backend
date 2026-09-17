<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'color'];

    public function masterServices(): HasMany
    {
        return $this->hasMany(MasterService::class);
    }
}
