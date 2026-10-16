<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'cr_number',
        'tax_number',
        'contact_person',
        'contact_email',
        'contact_phone',
        'city',
        'address',
        'website',
        'webhook_url',
        'webhook_secret',
        'platform_commission_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'platform_commission_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(CompanyApiKey::class, 'company_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'company_id');
    }

    public function debts(): HasMany
    {
        return $this->hasMany(CompanyDebt::class, 'company_id');
    }
}
