<?php

namespace App\Http\Middleware;

use App\CentralLogics\Helpers;
use App\Traits\ActivationClass;
use Closure;
use Illuminate\Support\Facades\Redirect;

class ActivationCheckMiddleware
{
    use ActivationClass;
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->is('admin/auth/login')) {
            $response = $this->actch();
            $data = json_decode($response->getContent(), true);
            if (!$data['active']) {
                return Redirect::away(base64_decode('aHR0cHM6Ly82YW10ZWNoLmNvbS9zb2Z0d2FyZS1hY3RpdmF0aW9u'))->send();
            }
        }
        return $next($request);
    }
}
