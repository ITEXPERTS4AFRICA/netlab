<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperRate
 */
class Rate extends Model
{
    protected $fillable = ['name','cents_per_minute','currency'];
}


