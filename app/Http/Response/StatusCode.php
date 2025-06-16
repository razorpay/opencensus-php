<?php

namespace RZP\Http\Response;

class StatusCode
{
    const SUCCESS      = 200;
    const REDIRECTION  = 300;
    const CLIENT_ERROR = 400;
    const SERVER_ERROR = 500;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;
}
