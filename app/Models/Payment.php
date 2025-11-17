<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'reservation_id',
        'transaction_id',
        'cinetpay_transaction_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'customer_name',
        'customer_surname',
        'customer_email',
        'customer_phone_number',
        'description',
        'cinetpay_response',
        'webhook_data',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'cinetpay_response' => 'array',
        'webhook_data' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Helpers
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }

    /**
     * Formater le montant pour affichage
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2, ',', ' ') . ' ' . $this->currency;
    }
}
