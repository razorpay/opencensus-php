<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\Base\Core;
use RZP\Models\FundAccount\Validation\Entity as Validation;

abstract class Base extends Core
{
    protected $validation;

    protected $account;

    public function __construct(Validation $validation)
    {
        parent::__construct();

        $this->validation = $validation;

        $this->account = $validation->fundAccount->account;
    }

    protected abstract function getAccount();

    public abstract function preProcessValidation();

    public abstract function processValidation();

    public abstract function postFundTransfer(array $input);
}
