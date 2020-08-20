<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Models\Payment;
use RZP\Gateway\Mozart;
use RZP\Exception\LogicException;
use RZP\Models\Payment\UpiMetadata;


/**
 * Trait MandateTrait
 * This trait will be used for Upi Mandate feature,
 * which enable gateway for routing request through mozart
 * @package RZP\Gateway\Upi\Base
 */
trait MandateTrait
{
    /**
     * Mandate create request
     * Route the requests to Mozart's mandate create.
     * @param array $input
     * @return array
     */
    public function mandateCreate(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->mandateCreate($input);
    }

    /**
     * Pre processing of mandate will happen in mozart.
     * Mozart takes care of decrypting the content.
     * @param array $input Gateway Input
     * @param string $gateway Gateway Name
     * @return mixed
     * @throws LogicException
     */
    public function preProcessMandateCallback(array $input, string $gateway)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->preProcessMandateCallback($input, $gateway);
    }

    /**
     * Fetches the payment id from gateway callback.
     * @param $response
     * @param $gateway
     * @return mixed
     * @throws LogicException
     */
    public function getPaymentIdFromMandateCallback($response, $gateway)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->getPaymentIdFromMandateCallback($response, $gateway);
    }

    /**
     * Process mandate create callback, and validates the details.
     * @param $input
     * @throws GatewayErrorException
     */
    public function mandateCreateCallback($input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->callback($input);
    }

    /**
     * Executes a authorized mandate
     * eg. In case upi one time mandate, Gateway capture is called,
     * which calls this mandate execute, to route it through mozart.
     * @param array $input
     */
    public function mandateExecute(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->mandateExecute($input);
    }

    /**
     * Revokes a mandate.
     * eg. In case upi one time mandate, Gateway reverse is called.
     * which calls this mandate reverse, to route it through mozart.
     * @param array $input
     */
    public function mandateRevoke(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->reverse($input);
    }

    /**
     * Verifies a mandate for authorized status (accepted mandate)
     * @param array $input
     */
    public function mandateStatus(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->verify($input);
    }

    /**
     * Determines if it is a mandate create request from Gateway Input.
     * @param $input
     * @return bool
     */
    protected function isMandateCreateRequest($input): bool
    {
        // Upi OTM payments
        if (($this->isUpiOtm($input) === true) and
            ($this->getPaymentStatus($input) === Payment\Status::CREATED))
        {
            return true;
        }

        return false;
    }

    protected function getPaymentStatus($input)
    {
        return $input['payment']['status'];
    }

    /**
     * Determines if it is a mandate create callback, from the Gateway Input
     * E.g. For one time mandate, We are depending on upi_metadata entity for
     * identifying if the payment is otm or not.
     * @param $input
     * @return bool
     */
    protected function isMandateProcessedCallback($input): bool
    {
        // Upi OTM payments
        if ($this->isUpiOtm($input) === true and
            ($this->getPaymentStatus($input) === Payment\Status::CREATED))
        {
            return true;
        }

        return false;
    }

    /**
     * Determines if it is a mandate execute request.
     * @param $input
     * @return bool
     */
    protected function isMandateExecuteRequest($input): bool
    {
        if (($this->isUpiOtm($input) === true) and
            ($input['payment']['status'] === Payment\Status::AUTHORIZED))
        {
            return true;
        }

        return false;
    }

    /**
     * Determines if it is mandate status request when verify called in gateway.
     * @param $input
     * @return bool
     */
    protected function isMandateStatusRequest($input): bool
    {
        if ($this->isUpiOtm($input) === true)
        {
            return true;
        }

        return  false;
    }

    /**
     * Determines if it is a mandate revoke request for gateway input.
     * @param $input
     * @return bool
     */
    protected function isMandateRevoke($input): bool
    {
        if (($input['payment']['gateway_captured'] !== true) and
            ($this->isUpiOtm($input) === true))
        {
            return true;
        }

        return false;
    }

    // OTM Helpers

    /**
     * Determines if it is UPI OTM Payment,
     * by upi_metadata entity attached in gateway input.
     * @param $input
     * @return bool
     */
    protected function isUpiOtm($input): bool
    {
        if ((isset($input[Payment\Method::UPI][UpiMetadata\Entity::TYPE]) === true) and
            ($input[Payment\Method::UPI][UpiMetadata\Entity::TYPE] === UpiMetadata\Type::OTM))
        {
            return true;
        }

        return false;
    }

    /**
     * Creates a mozart gateway instance,
     * with the mode set from gateway.
     * @return \RZP\Gateway\Mozart\Gateway
     */
    protected function getMozartGatewayWithModeSet()
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        $gateway->setMode($this->getMode());

        return $gateway;
    }
}
