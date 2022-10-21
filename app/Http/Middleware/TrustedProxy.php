<?php
namespace App\Http\Middleware;

use Closure;
use Config;
Use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustedProxy extends Middleware
{
    const HEADER_CLIENT_IP      = 'client_ip';
    const HEADER_CLIENT_PROTO   = 'client_proto';
    const HEADER_CLIENT_PORT    = 'client_port';
}
