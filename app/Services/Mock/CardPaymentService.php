<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment;
use RZP\Exception\BaseException;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Services\CardPaymentService as BaseCardPaymentService;

class CardPaymentService extends BaseCardPaymentService
{

    public function action(string $gateway, string $action, array $input): array
    {
        if ($action === 'fail')
        {
            $this->throwServiceErrorException(new BaseException('timed out or something'));
        }

        return $input;
    }

    public function fetchMultiple(string $entityName, array $input): array
    {
        return [];
    }

    public function fetch(string $entityName, string $id, $input)
    {
        return [];
    }


    public function authorizeAcrossTerminals(Payment\Entity $payment, array $gatewayInput, array $terminals)
    {
        return [];
    }
  
    public function fetchAuthorizationData(array $input)
    {
        $paymentId = $input['payment_ids'][0];

        $fields = $input['fields'];

        $dummyData = [
            Reconciliate::GATEWAY_TRANSACTION_ID    => '1234456789',
            Reconciliate::AUTH_CODE                 => '',
            'status'                                => 'success'
        ];

        $response = [];

        // Add the asked fields in response
        foreach ($fields as $field)
        {
            $response[$field] = $dummyData[$field] ?? null;
        }

        return [
            $paymentId => $response
        ];
    }
}
