<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = ['user_id','lab_id','rate_id','start_at','end_at','status','estimated_cents','notes'];
    protected $casts = ['start_at' => 'datetime','end_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function lab(): BelongsTo { return $this->belongsTo(Lab::class); }
    public function rate(): BelongsTo { return $this->belongsTo(Rate::class); }
    public function usageRecord(): HasOne { return $this->hasOne(UsageRecord::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}


