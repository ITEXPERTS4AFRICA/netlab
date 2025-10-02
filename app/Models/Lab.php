<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lab extends Model
{
    protected $fillable = ['cml_id','created','modified','lab_description','node_count','state','lab_title','owner','link_count','effective_permissions'];
    protected $casts = ['effective_permissions' => 'array'];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
