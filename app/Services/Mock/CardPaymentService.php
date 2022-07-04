<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment;
use RZP\Exception\BaseException;
use RZP\Reconciliator\Base\Constants;
use RZP\Services\CardPaymentService as BaseCardPaymentService;

class CardPaymentService extends BaseCardPaymentService
{
    public function content(& $content, $action = '')
    {
        return $content;
    }

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
            Constants::RRN                       => '123412341234',
            Constants::STATUS                    => 'failed',
            Constants::AUTH_CODE                 => '',
            Constants::GATEWAY_TRANSACTION_ID    => '1234456789',
            Constants::NETWORK_TRANSACTION_ID    => '0392166726767771',
        ];

        $response = [];

        // Add the asked fields in response
        foreach ($fields as $field)
        {
            $response[$field] = $dummyData[$field] ?? null;
        }

        $return = [
            $paymentId => $response
        ];

        $this->content($return, 'fetchAuthorizationData');

        return $return;
    }

    public function fetchPaymentIdFromCapsPIDs(array $input)
    {
        return [];
    }
}
