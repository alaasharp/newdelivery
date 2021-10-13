<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use App\Driver;
use App\DeliveryBoyApiToken;
use Auth;

class DriverAuth
{
    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $apiToken = DeliveryBoyApiToken::where('token', $request->header('token'))->first();
        if ($apiToken != null) {
            Auth::guard('drivers')->login($apiToken->deliveryBoy);
            $request->user = $apiToken->deliveryBoy;
            $request->apiToken = $apiToken;
            return $next($request);
        }
        return response()->json(['error' => 'Not authorized'], 401);
    }
}
