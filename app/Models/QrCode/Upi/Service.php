<?php

namespace RZP\Models\QrCode\Upi;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\QrCode;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;

class Service extends Base\Service
{
    public function processPayment(array $input, string $referenceId, string $gateway)
    {
        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $data = $gatewayClass->getParsedDataFromUnexpectedCallback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
            'data'          => $data,
            'gateway'       => $gateway,
            'reference_id'  => $referenceId,
            'qr_code'       => 1,
        ]);

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $data['terminal']);

        $response = (new Core)->processPayment($input, $referenceId, $data, $terminal);

        return $response;
    }
}
