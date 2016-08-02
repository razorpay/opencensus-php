<?php

namespace App\Http\Middleware;

use Closure;
use Input;
use App\Http\SlackResponse;

class Slack {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $slackToken = config('razorpay.slack.command_token');

        $tokenFromInput = Input::get('token', false);

        if ($slackToken !== $tokenFromInput)
        {
            return SlackResponse::jsonResponse("Invalid Slack Token");
        }

        return $next($request);
    }
}
