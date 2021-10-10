<?php

namespace RZP\Models\Survey\Response;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * @param array $input
     * @return array
     */
    public function processSurveyWebhook(array $input)
    {
        (new Validator)->validateInput(Validator::TYPEFORM_WEBHOOK,
            $input);

        $response = $this->core->processSurveyWebhook($input);

        return $response;
    }

    /**
     * @param array $input
     * @return array
     */
    public function pushTypeFormResponsesToDataLake(array $input)
    {
        return $this->core->pushTypeFormResponsesToDataLake($input);
    }
}
