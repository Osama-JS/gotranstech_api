<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'withdrawal_request_id',
        'principal_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }
}
