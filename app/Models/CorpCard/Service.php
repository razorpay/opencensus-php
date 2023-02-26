<?php

namespace RZP\Models\CorpCard;

use Request;
use ApiResponse;
use RZP\Models\Base;

class Service extends Base\Service
{
    protected $validator;

    public function __construct()
    {
        parent::__construct();
        $this->validator = (new Validator());
    }

    public function onboardCapitalCorpCardForPayouts($request)
    {
        $this->validator->validateInput('onboardccc', $request);

        $merchantId = $request['merchant_id'];

        return $this->core()->onboardCapitalCorpCardForPayouts($merchantId, $request);
    }
}
