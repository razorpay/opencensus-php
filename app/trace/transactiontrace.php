<?php

namespace Trace;

use Monolog\Logger;
use Trace\Trace;

class TransactionTrace extends Trace
{

    protected $component = 'transaction';

    protected static $compulsoryFields = array(
        'message',);
}