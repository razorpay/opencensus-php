<?php

namespace RZP\Models\Typeform;

use Request;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;

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

    public function handleOnRejectWorkflowAction(Action\Entity $action)
    {
        $input = Request::all();

        $this->core()->processWorkflowRequestRejection($action, $input);
    }
}
