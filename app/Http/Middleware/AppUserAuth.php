<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use App\ApiUser;
use App\ApiToken;
use Auth;

class AppUserAuth
{
    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $requestphp
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $apiToken = ApiToken::where('token', $request->header('token'))->first();
        if ($apiToken != null) {
            Auth::guard('app_users')->login($apiToken->customer);
            $request->user = $apiToken->customer;
            if ($request->customer_id && $request->customer_id != $request->user->id)
                return response()->json(['error' => 'Not authorized'], 401);
            $request->user['addresses'] = $apiToken->customer->addresses;
            $request->apiToken = $apiToken;
            return $next($request);
        }
        return response()->json(['error' => 'Not authorized'], 401);
    }
}
