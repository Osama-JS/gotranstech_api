<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'external_task_id',
        'task_number',
        'title',
        'description',
        'pickup_city',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_city',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'funding_amount',
        'company_commission_rate',
        'platform_commission_amount',
        'estimated_delivery_time',
        'expires_at',
        'status',
        'funded_by_investor_id',
        'funded_at',
        'withdrawal_request_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'funding_amount' => 'decimal:2',
            'company_commission_rate' => 'decimal:2',
            'platform_commission_amount' => 'decimal:2',
            'pickup_lat' => 'decimal:7',
            'pickup_lng' => 'decimal:7',
            'dropoff_lat' => 'decimal:7',
            'dropoff_lng' => 'decimal:7',
            'expires_at' => 'datetime',
            'funded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_id');
    }

    public function fundedByInvestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'funded_by_investor_id');
    }

    public static function generateTaskNumber(): string
    {
        return 'TSK-' . date('Ymd') . '-' . rand(10000, 99999);
    }
}
