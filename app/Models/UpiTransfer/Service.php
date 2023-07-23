<?php

namespace RZP\Models\UpiTransfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Account;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Models\UpiTransferRequest;

class Service extends Base\Service
{
    protected $core;

    protected $terminal;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function processUpiTransferPayment($input, $gateway)
    {
        $this->trace->info(
            TraceCode::UPI_TRANSFER_PAYMENT_PROCESS_REQUEST,
            [
                'input'   => $input,
                'gateway' => $gateway,
            ]);

        $valid           = false;
        $gatewayResponse = [];

        try
        {
            [$terminal, $gatewayResponse] = $this->computeGatewayResponseAndTerminal($input, $gateway);

            $upiTransferRequest = (new UpiTransferRequest\Service())->create($gatewayResponse['upi_transfer_data'],
                                                                             $gatewayResponse['callback_data']);

            $upiTransferRequestId = $upiTransferRequest ? $upiTransferRequest->getPublicId() : null;

            $valid = $this->core->processPayment($gatewayResponse, $terminal, $upiTransferRequestId);
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

    private function computeGatewayResponseAndTerminal($input, $gateway)
    {
        $this->determineAndSetMode();

        $gatewayClass = $this->getGatewayClass($input, $gateway);

        $gatewayResponse = Tracer::inSpan(['name' => HyperTrace::UPI_TRANSFER_PRE_PROCESS_CALLBACK],
            function() use ($input, $gatewayClass)
            {
                return $gatewayClass->preProcessServerCallback($input, false, true);
            });

        $terminal = Tracer::inSpan(['name' => HyperTrace::UPI_TRANSFER_FETCH_TERMINAL],
            function() use ($gatewayResponse, $gateway, $gatewayClass)
            {
                return $this->terminal ?: $this->getTerminalFromGatewayResponse($gatewayResponse, $gateway, $gatewayClass);
            });

        $gatewayResponse = $gatewayClass->getUpiTransferData($gatewayResponse);

        return [$terminal, $gatewayResponse];
    }

    public function processUpiTransferPaymentInternal($input, $gateway)
    {
        $valid           = false;
        $gatewayResponse = [];

        try
        {
            [$terminal, $gatewayResponse] = $this->computeGatewayResponseAndTerminal($input, $gateway);

            $upiTransferData = $gatewayResponse['upi_transfer_data'];

            $payeeVpa = $upiTransferData['payee_vpa'];

            $upiTransferData['payee_vpa'] = strtolower($payeeVpa);

            $upiTransfer = $this->repo
                                ->upi_transfer
                                ->findByProviderReferenceIdAndPayeeVpaAndAmount($upiTransferData['provider_reference_id'],
                                                                                $upiTransferData['payee_vpa'],
                                                                                $upiTransferData['amount']);

            if ($upiTransfer !== null)
            {
                return $this->getUpiTransferResponseInternal($upiTransfer);
            }

            $upiTransferRequest = (new UpiTransferRequest\Service())->create($gatewayResponse['upi_transfer_data'],
                                                                             $gatewayResponse['callback_data']);

            $valid = $this->core->processPayment($gatewayResponse, $terminal, $upiTransferRequest->getPublicId());


            if ($valid === true)
            {
                $upiTransfer = $this->repo
                                    ->upi_transfer
                                    ->findByProviderReferenceIdAndPayeeVpaAndAmount($upiTransferData['provider_reference_id'],
                                                                                    $upiTransferData['payee_vpa'],
                                                                                    $upiTransferData['amount']);

                return $this->getUpiTransferResponseInternal($upiTransfer);
            }
        }
        catch (\Exception $e)
        {
            return $this->getUpiTransferResponseInternal(null, $e->getMessage());
        }

        $upiTransferRequest = $this->repo->upi_transfer_request->findByPublicId($upiTransferRequest->getPublicId());

        return $this->getUpiTransferResponseInternal(null, $upiTransferRequest->getErrorMessage());
    }

    private function getUpiTransferResponseInternal($upiTransfer = null, $errorMessage = null)
    {
        if ($upiTransfer !== null)
        {
            $payment = $upiTransfer->payment;

            $response = ['payment' => $payment->toArrayRecon()];

            $response['refunds'] = $payment->refunds->toArrayRecon();

            return $response;
        }
        else
        {
            throw new Exception\ServerErrorException($errorMessage, ErrorCode::SERVER_ERROR_UPI_TRANSFER_PROCESSING_FAILED);
        }
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

//        $this->terminal = $this->getTerminalFromCallback($input, $gatewayClass, $gateway);

        $gatewayClass->setGatewayParams($input, $this->mode, $this->terminal);

        return $gatewayClass;
    }

    /*
   protected function getTerminalFromCallback($gatewayRequest, $gatewayClass, $gateway)
   {
       if (method_exists($gatewayClass, 'getTerminalDetailsFromCallbackIfApplicable') !== true)
       {
           return null;
       }

      $terminalDetails = $gatewayClass->getTerminalDetailsFromCallbackIfApplicable($gatewayRequest);

       //Earlier flow was,to create a new terminal with exactly same configs every time a merchant requests for a new custom prefix for virtual vpa
       //From now on all the payment will go via single terminal.
       $terminalDetails[Terminal\Entity::MERCHANT_ID] = Account::SHARED_ACCOUNT;

       $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $terminalDetails);

       if ($terminal !== null)
       {
           return $terminal;
       }

       unset($terminalDetails[Terminal\Entity::MERCHANT_ID]);

       $terminals = $this->repo->terminal->getByParams($terminalDetails);

       if ($terminals->count() !== 0)
       {
           return $terminals->first();
       }
       else
       {
           throw new Exception\LogicException(
               'No terminal found for upi transfer',
               null,
               [
                   'gateway_response' => $gatewayRequest,
                   'gateway'          => $gateway,
               ]
           );
       }
    }*/

    protected function getTerminalFromGatewayResponse($gatewayResponse, $gateway, $gatewayClass)
    {
        $terminal          = null;
        $gatewayMerchantId = null;

        $terminalDetails[Terminal\Entity::MERCHANT_ID] = Account::SHARED_ACCOUNT;

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
            {
                $terminalDetails[Terminal\Entity::GATEWAY_MERCHANT_ID] = $gatewayResponse[Fields::MERCHANT_ID];

                break;
            }
        }

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $terminalDetails);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'No terminal found for upi transfer',
                null,
                [
                    'gateway_response' => $gatewayResponse,
                    'gateway'          => $gateway,
                ]
            );
        }

        $gatewayClass->setGatewayParams($gatewayResponse, $this->mode, $terminal);

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
        $payment = Tracer::inSpan(['name' => HyperTrace::UPI_SERVICE_FIND_BY_PUBLIC_ID_AND_MERCHANT], function() use($paymentId)
        {
            return $this->repo
                        ->payment
                        ->findByPublicIdAndMerchant($paymentId, $this->merchant);
        });

        if ($payment->isUpiTransfer() === false)
        {
            return [];
        }

        $upiTransfer = Tracer::inSpan(['name' => HyperTrace::UPI_SERVICE_FIND_BY_PAYMENT_ID], function() use($payment)
        {
            return $this->repo
                        ->upi_transfer
                        ->findByPaymentId($payment->getId());
        });


        $response = $upiTransfer->toArrayPublic();

        // UPI transfer doesn't include VA in a public setter,
        // but it is required in this response. Adding explcitly.
        $response[Entity::VIRTUAL_ACCOUNT] = $upiTransfer->virtualAccount->toArrayPublic();

        return $response;
    }

    protected function determineAndSetMode()
    {
        if ($this->app['basicauth']->getMode() !== null)
        {
            return;
        }

        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Gets mode per route and sets application & db mode.
        $this->mode = str_contains($routeName, 'test') ? Mode::TEST : Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection($this->mode);
    }
}
