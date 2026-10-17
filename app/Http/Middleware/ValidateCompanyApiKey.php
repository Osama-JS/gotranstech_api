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
        // 1. Try Laravel's bearerToken() which checks Authorization, HTTP_AUTHORIZATION, and REDIRECT_HTTP_AUTHORIZATION
        $plainTextKey = $request->bearerToken();

        // 2. If null, check raw headers and server environment
        if (!$plainTextKey) {
            $header = $request->header('Authorization') 
                ?? $request->server('HTTP_AUTHORIZATION') 
                ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');

            if ($header && str_starts_with($header, 'Bearer ')) {
                $plainTextKey = trim(substr($header, 7));
            }
        }

        // 3. Fallback to X-Api-Key header (often used by clients and never stripped by proxies/FastCGI)
        if (!$plainTextKey) {
            $plainTextKey = $request->header('X-Api-Key') ?? $request->header('x-api-key');
        }

        if (!$plainTextKey) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized: Missing or invalid Authorization header. Expected Bearer token format or X-Api-Key header.',
            ], 401);
        }

        $plainTextKey = trim($plainTextKey);
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
