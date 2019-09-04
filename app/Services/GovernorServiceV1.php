<?php

namespace RZP\Services;

use Requests_Session;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class GovernorServiceV1
{
    const CREATE_NAMESPACE  =   [
        'url'       =>  "clients/:client_id/namespaces",
        'method'    =>  "POST",
    ];

    const LIST_NAMESPACES  =   [
        'url'       =>  "clients/:client_id/namespaces",
        'method'    =>  "GET",
    ];

}
