<?php

namespace RZP\Gateway\Blade\Mock\Xml;

class Response
{
    public function enrolledValidResponse(string $paymentId)
    {
       return '<?xml version="1.0" encoding="UTF-8"?><ThreeDSecure><Message id="' . $paymentId . '"><VERes><version>1.0.2</version><CH><enrolled>Y</enrolled><acctID>471054133</acctID></CH><url>https://www.acs.com</url><protocol>ThreeDSecure</protocol></VERes></Message></ThreeDSecure>';
    }

    public function notEnrolledValidResponse(string $paymentId)
    {
        return '<?xml version="1.0" encoding="UTF-8"?><ThreeDSecure><Message id="' . $paymentId . '"><VERes><version>1.0.2</version><CH><enrolled>N</enrolled></CH></VERes></Message></ThreeDSecure>';
    }

    public function differentMessageResponse(string $paymentId)
    {
       return '<?xml version="1.0" encoding="UTF-8"?><ThreeDSecure><Message id="RANDOM"><VERes><version>1.0.2</version><CH><enrolled>Y</enrolled><acctID>471054133</acctID></CH><url>https://www.acs.com</url><protocol>ThreeDSecure</protocol></VERes></Message></ThreeDSecure>';
    }

    public function blankMessageResponse(string $paymentId)
    {
        return '<?xml version="1.0" encoding="UTF-8"?><ThreeDSecure/>';
    }

    public function invalidVersionFormat(string $paymentId)
    {
        return '<?xml version="1.0" encoding="UTF-8"?><ThreeDSecure><Message id="' . $paymentId. '"><VERes><version>2</version><CH><enrolled>Y</enrolled><acctID>487111127</acctID></CH><url>https://www.acs.com</url><protocol>ThreeDSecure</protocol></VERes></Message></ThreeDSecure>';
    }
}
