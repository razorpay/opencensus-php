<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Exception;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Models\FundAccount\Validation\Entity as Validation;

class Vpa extends Base
{
    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

    /**
     * @throws Exception\LogicException
     */
    public function validateRetry()
    {
        throw new Exception\LogicException('Not supported for source type: Vpa');
    }

    public function preProcessValidation()
    {
        try
        {
            // TODO = call vpa validation function here
        }
        catch (\Throwable $e)
        {

        }
    }

    public function setDefaultValuesForValidation()
    {
        if ($this->validation->getAmount() === null)
        {
            $this->validation->setAmount(Constants::DEFAULT_VPA_VALIDATION_AMOUNT);
        }

        if ($this->validation->getCurrency() === null)
        {
            $this->validation->setCurrency(Constants::DEFAULT_PENNY_TESTING_CURRENCY);
        }
    }
}
