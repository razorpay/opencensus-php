<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class GatewayTrace extends Trace
{

    protected $component = 'gateway';

    protected static $compulsoryFields = array(
        'message',
        'payment_id');
}