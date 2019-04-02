<?php

namespace App\Http\Middleware;

use App\User;
use Request;
use Closure;
use App\Http\Headers;

class JWTValidate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $jwtToken = Request::header(Headers::JWT_TOKEN);

        (new User\Service())->validateJWT($jwtToken);
    }
}

