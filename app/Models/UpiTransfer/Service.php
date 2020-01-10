<?php

namespace RZP\Models\UpiTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Mindgate\ResponseFields;

class Service extends Base\Service
{
    protected $core;

    protected $terminals;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function processPaymentForUpiMindgate($input)
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => $input,
                'gateway' => Payment\Gateway::UPI_MINDGATE,
            ]);

        $valid           = false;
        $gatewayResponse = [];

        try
        {
            $this->determineAndSetMode();

            $this->terminals = $this->getTerminal($input);

            $gatewayClass = $this->getGatewayClass($input, Payment\Gateway::UPI_MINDGATE);

            $gatewayResponse = $gatewayClass->preProcessServerCallback($input);

            $gatewayResponse = $gatewayClass->getUpiTransferData($gatewayResponse);

            $valid = $this->core->processPayment($gatewayResponse, $this->terminals);
        }
        catch (\Exception $e)
        {
            $this->core->alertException($e, $gatewayResponse);
        }

        return [
            'valid'          => $valid,
            'message'        => null,
            'transaction_id' => $gatewayResponse['upi_transfer_data'][GatewayResponseParams::PROVIDER_REFERENCE_ID] ?? '',
        ];
    }

    protected function getGatewayClass($input, string $gateway)
    {
        if (Payment\Gateway::isValidUpiTransferGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway is invalid',
                'gateway',
                [
                    'gateway' => $gateway
                ]);
        }

        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $terminal = $this->terminals->first();

        $gatewayClass->setGatewayParams($input, $this->mode, $terminal);

        return $gatewayClass;
    }

    protected function getTerminal($gatewayRequest)
    {
        $terminalDetails = [];

        if (isset($gatewayRequest[ResponseFields::CALLBACK_RESPONSE_PGMID]) === true)
        {
            $terminalDetails[Terminal\Entity::GATEWAY_MERCHANT_ID] = $gatewayRequest[ResponseFields::CALLBACK_RESPONSE_PGMID];
        }
        else
        {
            $terminalDetails[Terminal\Entity::UPI]     = true;
            $terminalDetails[Terminal\Entity::GATEWAY] = Payment\Gateway::UPI_MINDGATE;
            $terminalDetails[Terminal\Entity::TYPE]    = Terminal\Type::UPI_TRANSFER;
        }

        $terminals = $this->repo->terminal->getByParams($terminalDetails);

        if ($terminals === null or sizeof($terminals) === 0)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                [
                    'gateway_response' => $gatewayRequest,
                ]
            );
        }

        return $terminals;
    }

    /**
     * This is used by the payment_upi_transfer_fetch route. UPI transfer
     * public entity contains payer VPA info for use by the merchant.
     *
     * @param string $paymentId
     *
     * @return array
     */
    public function fetchForPayment(string $paymentId)
    {
        $payment = $this->repo
                        ->payment
                        ->findByPublicIdAndMerchant($paymentId, $this->merchant);

        if ($payment->isUpiTransfer() === false)
        {
            return [];
        }

        $upiTransfer = $this->repo
                            ->upi_transfer
                            ->findByPaymentId($payment->getId());

        $response = $upiTransfer->toArrayPublic();

        // UPI transfer doesn't include VA in a public setter,
        // but it is required in this response. Adding explcitly.
        $response[Entity::VIRTUAL_ACCOUNT] = $upiTransfer->virtualAccount->toArrayPublic();

        return $response;
    }

    protected function determineAndSetMode()
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Gets mode per route and sets application & db mode.
        $mode = str_contains($routeName, 'test') ? Mode::TEST : Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection($mode);
    }
}
