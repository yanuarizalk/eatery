<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // If user has 2FA enabled, check if they have verified 2FA
        if ($user->two_factor_enabled) {
            // Check if the JWT token has the '2fa_verified' claim
            try {
                $token = JWTAuth::getToken();
                if (!$token) {
                    throw new \Exception('Token not provided');
                }
                $payload = JWTAuth::getPayload($token);

                if (!$payload->get('2fa_verified')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Two-factor authentication required',
                        'requires_2fa' => true
                    ], 403);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token: ' . $e->getMessage()
                ], 401);
            }
        }

        return $next($request);
    }
}
