<?php

namespace RZP\Tests\Unit\Mock;

use App;
use RZP\Models\Merchant;
use RZP\Models\Payment\Processor;

class ProcessorMock extends Processor\Processor
{
    public function __construct(Merchant\Entity $merchant)
    {
        parent::__construct($merchant);
    }

    public function processInputForUpi(array & $input)
    {
        $this->preProcessForUpiIfApplicable($input);
    }

    public function runBuildPayment(array $input)
    {
        return $this->buildPaymentEntity($input);
    }

    public function runUpdatePaymentWithOptimizerGatewayData($payment, $response)
    {
        return $this->updatePaymentWithOptimizerGatewayData($payment,$response);
    }
}
