<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use \RZP\Gateway\Upi\Sbi\Mock\Server as Sbi;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use \RZP\Gateway\Upi\Axis\Mock\Gateway as Axis;
use \RZP\Gateway\Upi\ICICI\Mock\Gateway as ICICI;
use RZP\Gateway\Mozart\Mock\Upi\MozartUpiResponse;
use \RZP\Gateway\Upi\Yesbank\Mock\Server as Yesbank;
use RZP\Gateway\Upi\Juspay\Fields as UpiJuspayFields;
use \RZP\Gateway\Upi\Mindgate\Mock\Gateway as Mindgate;

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

        $vpa = empty($input[10]) === false ? $input[10]: 'customer@okicici';

        $response->mergeUpi([
            UpiEntity::VPA                  => $vpa,
            UpiEntity::STATUS_CODE          => $input[7],
            UpiEntity::NPCI_REFERENCE_ID    => $input[12],
            UpiEntity::NPCI_TXN_ID          => $input[11],
            UpiEntity::MERCHANT_REFERENCE   => $input[1],
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $input[3],
        ]);

        $response->setStatus(true);

        $response->setTerminal([
            Terminal\Entity::VPA     => 'testvpa@yesb',
            Terminal\Entity::GATEWAY =>'upi_yesbank',
        ]);

        if ($input[7] !== '00')
        {
            $response->setSuccess(false);

            $response->setStatus(false);

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

    public function billdesk_optimizer($entities)
    {
        $data = json_decode($entities['body'], true);
        $paymentId = $data['orderid'];
        $response = [
            'data' =>
                [
                    'paymentId' => $paymentId,
                    'status' => 'pre_process_successful',
                    '_raw' => '{\"payment_method_type\":\"upi\",\"discount\":\"0.00\",\"charge_amount\":\"10.05\",\"auth_status\":\"0399\",\"transaction_error_desc\":\"Collect expired\",\"transaction_date\":\"2023-01-23T19:18:59+05:30\",\"txn_process_type\":\"collect\",\"bankid\":\"HD5\",\"currency\":\"356\",\"objectid\":\"transaction\",\"mercid\":\"NEWINDINP2\",\"transactionid\":\"XHD50970836821\",\"transaction_error_code\":\"TRPPE0038\",\"transaction_error_type\":\"payment_processing_error\",\"additional_info\":{\"additional_info6\":\"NA\",\"additional_info1\":\"NA\",\"additional_info10\":\"NA\",\"additional_info3\":\"NA\",\"additional_info2\":\"NA\",\"additional_info5\":\"NA\",\"additional_info4\":\"NA\",\"additional_info7\":\"NA\",\"additional_info9\":\"NA\",\"additional_info8\":\"NA\"},\"itemcode\":\"DIRECT\",\"surcharge\":\"0.00\",\"amount\":\"10.05\",\"orderid\":\"'.$paymentId.'\"}',
                ],
            'error' => NULL,
            'external_trace_id' => 'DUMMY_REQUEST_ID',
            'mozart_id' => 'DUMMY_MOZART_ID',
            'next' => [],
            'success' => true,
        ];
        return $response;
    }

    public function mozart($entities)
    {
        $gateway = $entities['gateway']['gateway'] ?? 'mozart';

        switch ($gateway)
        {
            case 'upi_airtel':
                return $this->upi_airtel($entities);
        }

       return json_decode($entities['gateway']['payload']);
    }

    public function upi_airtel($entities)
    {
        assertTrue($entities['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE);

        $data = json_decode($entities['gateway']['payload'], true);
        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $response->mergeUpi([
            UpiEntity::VPA                  => $data['payerVPA'] ?? '',
            UpiEntity::STATUS_CODE          => $data['errorCode'],
            UpiEntity::NPCI_REFERENCE_ID    => $data['rrn'],
            UpiEntity::NPCI_TXN_ID          => $data['txnRefNo'] ?? "",
            UpiEntity::MERCHANT_REFERENCE   => $data['hdnOrderID'],
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $data['amount']*100,
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID2   => $data['payeeVPA'] ?? 'razorpay@mairtel',
            Terminal\Entity::GATEWAY                => 'upi_airtel',
        ]);

        if ($data['code'] !== '0')
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

        $response = $response->toArray();

        unset($response['next']);

        if (empty($response['error']) === true)
        {
            unset($response['error']);
        }

        return $response;
    }

    public function upi_sbi($entities)
    {
        assertTrue($entities['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE);

        $callbackData = json_decode($entities['gateway']['payload']['msg'], true);

        $data = (new Sbi())->decryptInput($entities)['apiResp'];

        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $response->mergeUpi([
            UpiEntity::VPA                  => $data['payerVPA'] ?? '',
            UpiEntity::STATUS_CODE          => $data['responseCode'],
            UpiEntity::NPCI_REFERENCE_ID    => $data['custRefNo'],
            UpiEntity::NPCI_TXN_ID          => $data['npciTransId'] ?? "",
            UpiEntity::MERCHANT_REFERENCE   => $data['pspRefNo'],
            UpiEntity::GATEWAY_PAYMENT_ID   => $data['upiTransRefNo'],
            UpiEntity::GATEWAY              => 'upi_sbi',
            'gateway_amount'                => $data['amount'] * 100,
            "npci_response_code"            => $data['status'],
            "gateway_status_code"           => $data['status'],
            "gateway_data"                  => "{\"addInfo2\":\"7971807546\"}"
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $data['amount'] * 100,
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID    => $callbackData['pgMerchantId'],
            Terminal\Entity::GATEWAY                => 'upi_sbi',
        ]);

        $response = $response->toArray();

        unset($response['next']);

        unset($response['error']);

        return $response;
    }

    public function upi_icici($entities)
    {
        assertTrue($entities['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE);

        // The gateway response is encrypted, but wrapped
        // in lines of 80-length. Decryption can't handle
        // this, so we remove any whitespace from the response
        // since this is base64, it only removes newlines
        $payload = preg_replace('/\s/', '', $entities['gateway']['payload']);

        $payload = base64_decode($payload, true);

        $data = (new Icici())->decrypt($payload);

        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $data = json_decode($data, true);

        $response->mergeUpi([
            UpiEntity::VPA                  => $data['PayerVA'],
            UpiEntity::STATUS_CODE          => $data['TxnStatus'],
            UpiEntity::NPCI_REFERENCE_ID    => $data['BankRRN'],
            UpiEntity::MERCHANT_REFERENCE   => $data['merchantTranId'],
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $data['PayerAmount']*100,
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID   => $data['merchantId'],
            Terminal\Entity::GATEWAY               => 'upi_icici',
        ]);

        if ($data['TxnStatus'] === 'FAILURE')
        {
            $response->setSuccess(false);

            if($data["PayerVA"] === "BT@icici")
            {
                $response->setError([
                    'description' => 'Transaction is pending (BT)',
                    'gateway_error_code' => 'BT',
                    'gateway_error_description' => 'Transaction is pending (BT)',
                    'gateway_status_code' => 200,
                    'internal_error_code' => 'GATEWAY_ERROR_TRANSACTION_PENDING',
                ]);

                $response->mergeUpi([
                    UpiEntity::STATUS_CODE => 'BT',
                ]);

            }
            else
            {
                $response->setError([
                    'description' => 'Debit has been failed',
                    'gateway_error_code' => 'U30',
                    'gateway_error_description' => 'Debit has been failed',
                    'gateway_status_code' => 200,
                    'internal_error_code' => 'GATEWAY_ERROR_DEBIT_FAILED',
                ]);

                $response->mergeUpi([
                    UpiEntity::STATUS_CODE => 'U30',
                ]);

            }

        }

        $response = $response->toArray();

        unset($response['next']);

        return $response;
    }

    public function upi_axis($entities)
    {
        assertTrue($entities['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE);

        $payload =  $entities['gateway']['payload'];

        $encryptedmessage = str_replace('\n','',$payload);

        $aesdecrypted = (new Axis())->decryptAes($encryptedmessage);

        $aesdecrypted = preg_replace('/[[:cntrl:]]/', '', $aesdecrypted);

        $data = json_decode($aesdecrypted, true);

        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $response->mergeUpi([
            UpiEntity::VPA                  => $data['customerVpa'] ?? '',
            UpiEntity::STATUS_CODE          => $data['gatewayResponseCode'],
            UpiEntity::NPCI_REFERENCE_ID    => $data['rrn'],
            UpiEntity::NPCI_TXN_ID          => $data['gatewayTransactionId'] ?? "",
            UpiEntity::MERCHANT_REFERENCE   => $data['merchantTransactionId'],
            UpiEntity::GATEWAY              => 'upi_axis',
            'gateway_amount'                => $data['transactionAmount'] * 100
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => $data['transactionAmount'] * 100,
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID    => $data['merchantId'],
            Terminal\Entity::GATEWAY                => 'upi_axis',
        ]);

        $response->setMeta([
            'response' =>    [
                'content'     => $payload,
                'plain'       => $data,
            ]
        ]);

        if ($data['gatewayResponseCode'] === 'U30')
        {
            $response->setSuccess(false);

            $response->setError([
                'description'               => 'Debit has been failed',
                'gateway_error_code'        => 'U30',
                'gateway_error_description' => 'Debit has been failed',
                'gateway_status_code'       =>  200,
                'internal_error_code'       => 'GATEWAY_ERROR_DEBIT_FAILED',
            ]);

            $response->mergeUpi([
                UpiEntity::STATUS_CODE => 'U30',
            ]);
        }

        $response = $response->toArray();

        unset($response['next']);

        return $response;
    }

    public function upi_mindgate($entities)
    {
        assertTrue($entities['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE);

        $payload = $entities['gateway']['payload'];

        try
        {
            $gateway = new Mindgate();

            $gateway->setTerminal($entities['terminal']);

            $data = $gateway->decryptGatewayResponse($payload['meRes']);
        }
        catch (Exception\GatewayErrorException $e)
        {
            $response = MozartUpiResponse::getDefaultInstanceForV2();

            $response->setSuccess(false);

            $response->setError([
                'description'               => 'Decryption Failed',
                'gateway_error_code'        => 'dFailed',
                'gateway_error_description' => 'Decryption Failed',
                'gateway_status_code'       =>  200,
                'internal_error_code'       => 'BAD_REQUEST_DECRYPTION_FAILED',
            ]);

            $response = $response->toArray();

            unset($response['next']);

            return $response;
        }

        $response = MozartUpiResponse::getDefaultInstanceForV2();

        $response->mergeUpi([
            UpiEntity::VPA                  => $data['payer_va'],
            UpiEntity::STATUS_CODE          => $data['respcode'],
            UpiEntity::NPCI_REFERENCE_ID    => $data['npci_upi_txn_id'],
            UpiEntity::MERCHANT_REFERENCE   => $data['payment_id'],
            UpiEntity::GATEWAY_PAYMENT_ID   => $data['upi_txn_id'],
        ]);

        $response->setPayment([
            Payment\Entity::CURRENCY          => 'INR',
            Payment\Entity::AMOUNT_AUTHORIZED => round(floatval($data['amount']) * 100),
        ]);

        $response->setTerminal([
            Terminal\Entity::GATEWAY_MERCHANT_ID   => $payload['pgMerchantId'],
            Terminal\Entity::GATEWAY               => 'upi_mindgate',
        ]);

        if($data['respcode'] === 'ZA')
        {
            $response->setSuccess(false);

            $response->setError([
                'description'               => 'Payment Declined',
                'gateway_error_code'        => 'ZA',
                'gateway_error_description' => 'Payment Declined',
                'gateway_status_code'       =>  200,
                'internal_error_code'       => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
            ]);
        }
        else if ($data['respcode'] === 'noTerminal')
        {
            $response->setSuccess(false);

            $response->setError([
                'description'               => 'Decryption Failed',
                'gateway_error_code'        => 'dFailed',
                'gateway_error_description' => 'Decryption Failed',
                'gateway_status_code'       =>  200,
                'internal_error_code'       => 'BAD_REQUEST_DECRYPTION_FAILED',
            ]);
        }

        $response = $response->toArray();

        unset($response['next']);

        return $response;
    }

    public function upi_kotak(array $entities): array
    {
        return $this->upi($entities);
    }

    public function upi_rzprbl(array $entities): array
    {
        return $this->upi($entities);
    }

    protected function upi(array $entities): array
    {
        assertTrue($entities['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE);
        $payload = json_decode($entities['gateway']['payload'], true);
        $response = MozartUpiResponse::getDefaultInstanceForV2();
        $payerVPA = 'vishnu@icici';
        $statusCode = '00';
        $amount = (string) $payload['amount'];

        switch ($payload[Payment\Entity::DESCRIPTION])
        {
            case 'payment_failed':
                $statusCode = 'U30';
                $response->setSuccess(false);
                $response->setError([
                    'description'               => 'Debit has been failed',
                    'gateway_error_code'        => 'U30',
                    'gateway_error_description' => 'Debit has been failed',
                    'gateway_status_code'       => 200,
                    'internal_error_code'       => 'GATEWAY_ERROR_DEBIT_FAILED',
                ]);
                break;
            case 'amount_mismatch':
                $amount = '1001';
        }

        $response->mergeUpi([
            'vpa' => $payload['vpa'] ?? $payerVPA,
            'status_code' => $statusCode,
            'npci_reference_id' => '002002002002',
            'merchant_reference' => $payload['id'],
        ]);

        $response->setPayment([
            'currency' => 'INR',
            'amount_authorized' => $amount
        ]);

        $response->setTerminal([
            'id' => $payload['terminal_id'],
        ]);

        $response = $response->toArray();

        unset($response['next']);

        return $response;

    }
}
