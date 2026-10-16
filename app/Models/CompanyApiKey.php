<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'key_name',
        'api_key_prefix',
        'api_key_hash',
        'permissions',
        'last_used_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_id');
    }

    public static function verifyKey(string $plainTextKey): ?self
    {
        $hash = hash('sha256', $plainTextKey);
        return self::where('api_key_hash', $hash)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
