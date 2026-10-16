<?php

namespace App\Http\Middleware;

use App\Models\CompanyApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCompanyApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized: Missing or invalid Authorization header. Expected Bearer token format.',
            ], 401);
        }

        $plainTextKey = trim(substr($header, 7));
        $apiKey = CompanyApiKey::verifyKey($plainTextKey);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized: Invalid, expired, or revoked API Key.',
            ], 401);
        }

        $company = $apiKey->company;
        if (!$company || $company->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden: The associated company account is inactive or suspended.',
            ], 403);
        }

        // Update last used timestamp
        $apiKey->update(['last_used_at' => now()]);

        // Attach company to request attributes
        $request->attributes->set('company', $company);
        $request->attributes->set('apiKey', $apiKey);

        return $next($request);
    }
}
