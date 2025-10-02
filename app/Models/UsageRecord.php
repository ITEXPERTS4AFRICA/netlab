<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    protected $fillable = ['reservation_id','user_id','lab_id','started_at','ended_at','duration_seconds','cost_cents'];
    protected $casts = ['started_at'=>'datetime','ended_at'=>'datetime'];

    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function lab(): BelongsTo { return $this->belongsTo(Lab::class); }
}


