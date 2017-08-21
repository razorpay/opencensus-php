<?php

namespace RZP\Gateway\Blade\Mock;

use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $parsedInput = simplexml_load_string($input);

        $parsedInput = json_decode(json_encode($parsedInput), true);

        return $this->makeResponse('<?xml version="1.0" encoding="UTF-8"?><ThreeDSecure><Message id="' . $parsedInput['Message']['@attributes']['id'] . '"><VERes><version>1.0.2</version><CH><enrolled>Y</enrolled><acctID>471054133</acctID></CH><url>https://www.acs.com</url><protocol>ThreeDSecure</protocol></VERes></Message></ThreeDSecure>');
    }
}
