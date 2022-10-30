<?php

namespace RZP\Models\QrCode\Upi;

use Razorpay\Trace\Logger;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;
use RZP\Models\QrPaymentRequest;

class Service extends Base\Service
{
    public function processPayment(array $input, string $referenceId, string $gateway)
    {
        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $data = $gatewayClass->getParsedDataFromUnexpectedCallback($input);

        try {
            $qrCode = (new QrCode\Repository())->findByMerchantReference($referenceId);

            $data['payment']['notes'] = $qrCode->getNotes()->toArray();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex,Logger::ERROR,TraceCode::QR_FETCH_BY_REFERENCE_FAILED);
        }

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
            'data'          => $data,
            'gateway'       => $gateway,
            'reference_id'  => $referenceId,
            'qr_code'       => 1,
        ]);

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $data['terminal']);

        if(isset($qrCode) === true and $qrCode->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            $terminal = $terminal->toArrayWithPassword();

            $terminal = (new Terminal\Service())->getEntityFromTerminalServiceResponse($terminal);
        }

        $qrPaymentRequest = (new QrPaymentRequest\Service())->create($this->getGatewayReponse($input),
                                                                     QrPaymentRequest\Type::UPI_QR);

        $response = (new Core)->processPayment($input, $referenceId, $data, $terminal, $qrPaymentRequest);

        return $response;
    }

    private function getGatewayReponse($input)
    {
        $gatewayResponse = [];

        $gatewayResponse['qr_data'] = [
            QrPaymentRequest\Entity::QR_CODE_ID            => $input['payment_id'],
            QrPaymentRequest\Entity::TRANSACTION_REFERENCE => $input['npci_upi_txn_id'],
            Payment\Entity::METHOD                         => Payment\Method::UPI
        ];

        $gatewayResponse['callback_data'] = $input;

        return $gatewayResponse;
    }

}
