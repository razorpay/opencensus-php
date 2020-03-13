<?php

namespace RZP\Models\Typeform;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{

    public function processTypeformWebhook(array $input)
    {
        $validator = new Validator();

        $validator->setStrictFalse();

        $validator->validateInput('typeform_webhook', $input);

        return ["authorization" => "cleared"];//temporary
    }

}
