<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Gateway\Mozart\Mock\Upi\MozartUpiResponse;
use \RZP\Gateway\Upi\Yesbank\Mock\Server as Yesbank;
use RZP\Gateway\Upi\Juspay\Fields as UpiJuspayFields;

class PreProcess extends Base\Mock\Server
{
    public function upi_juspay($input)
    {
        $body = $input['gateway']['body'];

        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $response->mergeUpi([
            UpiEntity::VPA                  => $body[UpiJuspayFields::PAYER_VPA],
            UpiEntity::STATUS_CODE          => $body[UpiJuspayFields::GATEWAY_RESPONSE_CODE],
            UpiEntity::GATEWAY_MERCHANT_ID  => $body[UpiJuspayFields::MERCHANT_ID],
            UpiEntity::NPCI_REFERENCE_ID    => $body[UpiJuspayFields::GATEWAY_REFERENCE_ID],
            UpiEntity::NPCI_TXN_ID          => $body[UpiJuspayFields::GATEWAY_TRANSACTION_ID],
            UpiEntity::MERCHANT_REFERENCE   => $body[UpiJuspayFields::MERCHANT_REQUEST_ID],
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $this->getIntegerFormattedAmount($body[UpiJuspayFields::AMOUNT]),
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID => $body[UpiJuspayFields::MERCHANT_ID]
        ]);

        if ($body[UpiJuspayFields::GATEWAY_RESPONSE_CODE] !== '00')
        {
            $response->setSuccess(false);

            $response->setError([
                'description'               => 'Collect Expired',
                'gateway_error_code'        => 'U69',
                'gateway_error_description' => 'Collect Expired',
                'gateway_status_code'       => 200,
                'internal_error_code'       => 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED',
            ]);
        }

        return $response->toArray();
    }

    public function upi_yesbank($entities)
    {
        $input = (new Yesbank())->decryptInput($entities['gateway']);

        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $response->mergeUpi([
            UpiEntity::VPA                  => $input[10],
            UpiEntity::STATUS_CODE          => $input[7],
            UpiEntity::NPCI_REFERENCE_ID    => $input[12],
            UpiEntity::NPCI_TXN_ID          => $input[11],
            UpiEntity::MERCHANT_REFERENCE   => $input[1],
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $input[3],
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID => 'YES0000000012026'
        ]);

        if ($input[7] !== '00')
        {
            $response->setSuccess(false);

            $response->setError([
                'description'               => 'Debit has been failed',
                'gateway_error_code'        => 'U30',
                'gateway_error_description' => 'Debit has been failed',
                'gateway_status_code'       =>  200,
                'internal_error_code'       => 'GATEWAY_ERROR_DEBIT_FAILED',
            ]);
        }

        return $response->toArray();
    }
}
