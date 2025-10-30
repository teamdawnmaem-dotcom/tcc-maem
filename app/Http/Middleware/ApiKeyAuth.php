<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiKeyAuth Middleware
 * 
 * This middleware is used on HOSTINGER (cloud server) to authenticate
 * incoming sync requests from the local development server.
 * 
 * The API_KEY in Hostinger .env must match CLOUD_API_KEY in local .env
 */
class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('Authorization');
        $expectedKey = 'Bearer ' . env('API_KEY');
        
        // Check if API key is provided and matches
        if (!$apiKey || $apiKey !== $expectedKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key'
            ], 401);
        }
        
        return $next($request);
    }
}
