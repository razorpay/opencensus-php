<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Upi;
use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Gateway extends Upi\Gateway
{
    use Upi\Npci\ClTrait;

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

    protected function inputSdk(): ArrayBag
    {
        return $this->input->get(Fields::SDK);
    }

    /**
     * These are validation issues not the scenario issues
     *
     * @param Response $response
     * @return mixed|null
     */
    protected function handleSdkCredential(Response $response)
    {
        $sdk = $this->inputSdk()->get(Upi\Npci\ClAction::GET_CREDENTIAL);

        if (empty($sdk) === true)
        {
            $response->setError(ErrorCode::BAD_REQUEST_PAYMENT_UPI_DEVICE_MISSING, 'Empty response from CL');
            return null;
        }

        if ($sdk === 'USER_ABORTED')
        {
            $response->setError(ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED, 'User aborted');
            return null;
        }

        if (is_array($sdk) === false)
        {
            $response->setError(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, 'Get Credential response must be array');
            return null;
        }

        if (isset($sdk['error']) === true)
        {
            $response->setError(ErrorCode::BAD_REQUEST_INVALID_DEVICE,
                $sdk['error']['errorText'] ?? null,
                $sdk['error']['errorCode'] ?? null);

            return null;
        }

        return $sdk;
    }
}
