<?php

namespace Jennairaderafaella\Inlite\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\Routing\Exception\MethodNotAllowedException
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the request is not AJAX or PJAX
        if (!($request->ajax() || $request->pjax())) {
            // If not AJAX or PJAX, throw a MethodNotAllowedException
            throw new MethodNotAllowedException(
                ['XMLHttpRequest'],
                "You cannot use this route without XMLHttpRequest."
            );
        }

        // If the request is AJAX or PJAX, proceed to the next middleware or route handler
        return $next($request);
    }
}
