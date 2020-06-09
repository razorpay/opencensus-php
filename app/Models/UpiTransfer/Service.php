<?php

namespace RZP\Models\UpiTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Account;
use RZP\Gateway\Upi\Mindgate\ResponseFields;

class Service extends Base\Service
{
    protected $core;

    protected $terminal;

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

            $this->terminal = $this->getTerminal($input);

            $gatewayClass = $this->getGatewayClass($input, Payment\Gateway::UPI_MINDGATE);

            $gatewayResponse = $gatewayClass->preProcessServerCallback($input);

            $gatewayResponse = $gatewayClass->getUpiTransferData($gatewayResponse);

            $valid = $this->core->processPayment($gatewayResponse, $this->terminal);
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

        $gatewayClass->setGatewayParams($input, $this->mode, $this->terminal);

        return $gatewayClass;
    }

    protected function getTerminal($gatewayRequest)
    {
        $terminalDetails = [];

        if (isset($gatewayRequest[ResponseFields::CALLBACK_RESPONSE_PGMID]) === true)
        {
            //Earlier flow was,to create a new terminal with exactly same configs every time a merchant requests for a new custom prefix for virtual vpa
            //From now on all the payment will go via single terminal.
            $terminalDetails[Terminal\Entity::GATEWAY_MERCHANT_ID] = $gatewayRequest[ResponseFields::CALLBACK_RESPONSE_PGMID];
            $terminalDetails[Terminal\Entity::MERCHANT_ID]         = Account::SHARED_ACCOUNT;

            $terminals = $this->repo->terminal->getByParams($terminalDetails);

            if ($terminals->count() === 0)
            {
                unset($terminalDetails[Terminal\Entity::MERCHANT_ID]);

                $terminals = $this->repo->terminal->getByParams($terminalDetails);
            }

            return $terminals->first();
        }
        else
        {
            $terminal = (new  Payment\Processor\TerminalProcessor())->getTerminalForUpiTransfer();
        }

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                [
                    'gateway_response' => $gatewayRequest,
                ]
            );
        }

        return $terminal;
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
