<?php

namespace App\Http\Middleware;

use App\Models\UserLogHistory;
use Closure;
use Illuminate\Http\Request;

class CheckDeviceId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $device_id = $request->header('device-id');
        if($device_id == '') {
            abort(response()->json(response_formatter(DEFAULT_400), 400));
        }

        $device = UserLogHistory::where('user_id', $request->user()->id)->where('device_id', $device_id)->where('is_active', 1)->first();
        if (isset($device)) {
            return $next($request);
        }

        abort(response()->json(response_formatter(DEFAULT_403), 403));
    }
}
