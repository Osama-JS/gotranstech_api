<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'user_type',
        'status',
        'avatar',
        'password',
    ];

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function getCompanyFundingWalletAttribute(): ?Wallet
    {
        return $this->wallets()->firstOrCreate(
            ['wallet_type' => 'company_funding'],
            ['currency' => 'SAR', 'balance' => 0.00, 'locked_balance' => 0.00]
        );
    }

    public function getCompanyDebtWalletAttribute(): ?Wallet
    {
        return $this->wallets()->firstOrCreate(
            ['wallet_type' => 'company_debt'],
            ['currency' => 'SAR', 'balance' => 0.00, 'locked_balance' => 0.00]
        );
    }
}
