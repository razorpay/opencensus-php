<?php

namespace RZP\Models\Typeform;

use RZP\Models\Base;

class Service extends Base\Service
{

    /**
     * @param array $input
     *
     * @return mixed
     */
    public function processTypeformWebhook(array $input)
    {
        $validator = new Validator();

        $validator->setStrictFalse();

        $validator->validateInput('typeform_webhook', $input);

        $response = $this->core()->processTypeformWebhook($input);

        return $response;
    }
}
