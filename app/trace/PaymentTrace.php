<?php

namespace Trace;

use Trace\Trace;

class PaymentTrace extends Trace
{

    protected $component = 'payment';

    protected static $compulsoryFields = array(
        'message',);
}