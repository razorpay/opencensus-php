<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Upi;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Mock\Scenario;

class Gateway extends Upi\Gateway
{
    const RAZORSHARP    = 'razorsharp';
    const RZPSHARP      = 'rzpsharp';
    const NORZPSHARP    = 'norzpsharp';

    protected $gateway = 'p2p_razor_sharp';

    /**
     * @var Upi\Mock\Scenario
     */
    protected $scenario;

    protected function shouldMockResponse(): bool
    {
        $this->scenario = Upi\Mock\Scenario::fromRequestId($this->context->getRequestId());

        return true;
    }

    protected function shouldMockSuccessResponse(): bool
    {
        return $this->scenario->isSuccess();
    }

    protected function isScenario(string $id): bool
    {
        return ($this->scenario->getId() === $id);
    }

    protected function handleFailureScenarios(Response $response, array $scenarios): bool
    {
        if (in_array($this->scenario->getId(), $scenarios) === true)
        {
            $response->setError($this->scenario->getCode(), $this->scenario->getDesc(), $this->scenario->getDesc());

            return true;
        }

        return false;
    }
}
