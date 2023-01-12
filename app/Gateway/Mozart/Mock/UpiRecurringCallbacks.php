<?php

namespace RZP\Gateway\Mozart\Mock;

use Str;
use RZP\App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\UpiMandate;

trait UpiRecurringCallbacks
{
    /******************************** PUBLIC *****************************************/

    public function getAsyncCallbackResponseMandateCreate(Payment\Entity $payment, bool $encrypted=false)
    {
        $gateway = $payment->getGateway();

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
                return $this->getAsyncCallbackResponseMandateCreateForIcici($payment, $encrypted);

            case Payment\Gateway::UPI_MINDGATE:
                return $this->getAsyncCallbackResponseMandateCreateForMindgate($payment);

            default:
                throw new Exception\AssertionException('Invalid gateway for ' . __FUNCTION__ . ' ' . $gateway);
        }
    }

    public function getAsyncCallbackResponseFirstDebit(Payment\Entity $payment)
    {
        $gateway = $payment->getGateway();

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
                return $this->getAsyncCallbackResponseFirstDebitForIcici($payment);

            case Payment\Gateway::UPI_MINDGATE:
                return $this->getAsyncCallbackResponseFirstDebitForMindgate($payment);

            default:
                throw new Exception\AssertionException('Invalid gateway for ' . __FUNCTION__ . ' ' . $gateway);
        }
    }

    public function getAsyncCallbackResponseAutoDebit(Payment\Entity $payment)
    {
        $gateway = $payment->getGateway();

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
                return $this->getAsyncCallbackResponseAutoDebitForIcici($payment);

            case Payment\Gateway::UPI_MINDGATE:
                return $this->getAsyncCallbackResponseAutoDebitForMindgate($payment);

            default:
                throw new Exception\AssertionException('Invalid gateway for ' . __FUNCTION__ . ' ' . $gateway);
        }
    }

    public function getAsyncCallbackResponsePause(UpiMandate\Entity $mandate)
    {
        $gateway = $mandate->token->terminal->getGateway();

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
                return $this->getAsyncCallbackResponsePauseForIcici($mandate);

            case Payment\Gateway::UPI_MINDGATE:
                return $this->getAsyncCallbackResponsePauseForMindgate($mandate);

            default:
                throw new Exception\AssertionException('Invalid gateway for ' . __FUNCTION__ . ' ' . $gateway);
        }
    }

    public function getAsyncCallbackResponseResume(UpiMandate\Entity $mandate)
    {
        $gateway = $mandate->token->terminal->getGateway();

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
                return $this->getAsyncCallbackResponseResumeForIcici($mandate);

            case Payment\Gateway::UPI_MINDGATE:
                return $this->getAsyncCallbackResponseResumeForMindgate($mandate);

            default:
                throw new Exception\AssertionException('Invalid gateway for ' . __FUNCTION__ . ' ' . $gateway);
        }
    }

    public function getAsyncCallbackResponseRevoke(UpiMandate\Entity $mandate)
    {
        $gateway = $mandate->token->terminal->getGateway();

        switch ($gateway)
        {
            case Payment\Gateway::UPI_ICICI:
                return $this->getAsyncCallbackResponseRevokeForIcici($mandate);

            case Payment\Gateway::UPI_MINDGATE:
                return $this->getAsyncCallbackResponseRevokeForMindgate($mandate);

            default:
                throw new Exception\AssertionException('Invalid gateway for ' . __FUNCTION__ . ' ' . $gateway);
        }
    }

    /******************************** PAYMENT ****************************************/

    protected function getAsyncCallbackResponseMandateCreateForIcici(Payment\Entity $payment, bool $encrypted=false)
    {
        $response = [
            'merchantId'        => '400660',
            'subMerchantId'     => '400660',
            'terminalId'        => '5094',
            'BankRRN'           => '019721040510',
            'merchantTranId'    => $this->getReferenceNumberForCallback($payment, 'create'),
            'PayerName'         => 'payer',
            'PayerMobile'       => '9876543210',
            'PayerVA'           => 'test@icici',
            'PayerAmount'       => '5',
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => '20200715211840',
            'TxnCompletionDate' => '20200715211843',
            'UMN'               => $payment['id'] . '@icici',
        ];

        $jsonResponse = json_encode($response);

        if ($encrypted === false)
        {
            return $jsonResponse;
        }

        $encryptedResponse = $this->getHybridEncryptedResponse($response);

        return $encryptedResponse;
    }

    protected function getAsyncCallbackResponseMandateCreateForMindgate($payment)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'HDFC000006002278',
                'pspRefNo'      => $this->getReferenceNumberForCallback($payment, 'create'),
            ],
            'mandateDtls' => [
                [
                    'custRefNo'            => '987654321',
                    'requestDate'          => '25 Jul 2019 03:20 PM',
                    'referenceNumber'      => $payment['id'],
                    'txnId'                => '',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'CREATE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => '',
                    'payerVpa'             => $payment['vpa'],
                    'payerName'            => '',
                    'payeeVpa'             => '',
                    'payeeName'            => '',
                    'status'               => 'ACTIVE',
                    'debitIfsc'            => 'HSBC0001850',
                    'debitAccount'         => '777777777777777',
                    'creditIfsc'           => 'SBIN0000001',
                    'creditAccount'        => '671176176817611',
                    'noOfDebit'            => 0,
                    'remainingDebit'       => 0,
                    'onBehalf_Of'          => 'PAYER',
                    'amt_rule'             => 'EXACT',
                    'has_update_authority' => 'N',
                    'shareToPayee'         => 'Y',
                    'create_date_time'     => '25 Jul 2019 03:20 PM',
                    'show_QR'              => 'Y',
                    'callback_type'        => 'MANDATE_STATUS',
                    'purpose_code'         => '00',
                    'message'              => 'Mandate created successfully'
                ]
            ],
        ];


        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => 'HDFC000006002278',
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
        ];

        return $response;
    }

    protected function getAsyncCallbackResponseFirstDebitForIcici($payment)
    {
        $response = [
            'merchantId'        => '400660',
            'subMerchantId'     => '400660',
            'terminalId'        => '5094',
            'BankRRN'           => '019721040510',
            'merchantTranId'    => $this->getReferenceNumberForCallback($payment, 'execte'),
            'PayerName'         => 'payer',
            'PayerMobile'       => '9876543210',
            'PayerVA'           => 'test@icici',
            'PayerAmount'       => '5',
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => '20200715211840',
            'TxnCompletionDate' => '20200715211843',
            'UMN'               => $payment['id'] . '@icici',
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseFirstDebitForMindgate($payment)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'HDFC000006002278',
                'pspRefNo'      => $this->getReferenceNumberForCallback($payment, 'execte'),
            ],
            'mandateDtls' => [
                [
                    'custRefNo'            => '987654321',
                    'requestDate'          => '25 Jul 2019 03:20 PM',
                    'referenceNumber'      => $payment['id'],
                    'txnId'                => '',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'EXECUTE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => '',
                    'payerVpa'             => $payment['vpa'],
                    'payerName'            => '',
                    'payeeVpa'             => '',
                    'payeeName'            => '',
                    'status'               => 'ACTIVE',
                    'debitIfsc'            => 'HSBC0001850',
                    'debitAccount'         => '777777777777777',
                    'creditIfsc'           => 'SBIN0000001',
                    'creditAccount'        => '671176176817611',
                    'noOfDebit'            => 0,
                    'remainingDebit'       => 0,
                    'onBehalf_Of'          => 'PAYER',
                    'amt_rule'             => 'EXACT',
                    'has_update_authority' => 'N',
                    'shareToPayee'         => 'Y',
                    'create_date_time'     => '25 Jul 2019 03:20 PM',
                    'show_QR'              => 'Y',
                    'callback_type'        => 'MANDATE_STATUS',
                    'purpose_code'         => '00',
                    'message'              => 'Mandate created successfully'
                ]
            ],
        ];

        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => 'HDFC000006002278',
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
        ];

        return $response;
    }

    protected function getAsyncCallbackResponseAutoDebitForIcici($payment)
    {
        $response = [
            'merchantId'        => '400660',
            'subMerchantId'     => '400660',
            'terminalId'        => '5094',
            'BankRRN'           => '019721040510',
            'merchantTranId'    => $this->getReferenceNumberForCallback($payment, 'execte'),
            'PayerName'         => 'payer',
            'PayerMobile'       => '9876543210',
            'PayerVA'           => 'localuser@icici',
            'PayerAmount'       => '500',
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => '20200715211840',
            'TxnCompletionDate' => '20200715211843',
            'UMN'               => $payment['id'] . '@icici',
        ];

        return json_encode($response);
    }

    /******************************** MANDATE ***************************************/

    protected function getAsyncCallbackResponsePauseForIcici($mandate)
    {
        $response = [
            'merchantId'        => '400660',
            'subMerchantId'     => '400660',
            'terminalId'        => '5094',
            'BankRRN'           => '019721040510',
            'merchantTranId'    => '12345678',
            'PayerName'         => 'payer',
            'PayerMobile'       => '9876543210',
            'PayerVA'           => 'test@icici',
            'PayerAmount'       => '5',
            'TxnStatus'         => 'SUSPEND-SUCCESS',
            'TxnInitDate'       => '20200715211840',
            'TxnCompletionDate' => '20200715211843',
            'UMN'               => $mandate['umn'],
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseResumeForIcici($mandate)
    {
        $response = [
            'merchantId'        => '400660',
            'subMerchantId'     => '400660',
            'terminalId'        => '5094',
            'BankRRN'           => '019721040510',
            'merchantTranId'    => '12345678',
            'PayerName'         => 'payer',
            'PayerMobile'       => '9876543210',
            'PayerVA'           => 'test@icici',
            'PayerAmount'       => '5',
            'TxnStatus'         => 'REACTIVATE-SUCCESS',
            'TxnInitDate'       => '20200715211840',
            'TxnCompletionDate' => '20200715211843',
            'UMN'               => $mandate['umn'],
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseRevokeForIcici($mandate)
    {
        $response = [
            'merchantId'        => '400660',
            'subMerchantId'     => '400660',
            'terminalId'        => '5094',
            'BankRRN'           => '019721040510',
            'merchantTranId'    => '12345678',
            'PayerName'         => 'payer',
            'PayerMobile'       => '9876543210',
            'PayerVA'           => 'test@icici',
            'PayerAmount'       => '5',
            'TxnStatus'         => 'REVOKE-SUCCESS',
            'TxnInitDate'       => '20200715211840',
            'TxnCompletionDate' => '20200715211843',
            'UMN'               => $mandate['umn'],
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseMandateUpdate($payment)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'HDFC000006002278',
                'pspRefNo'      =>  $payment['id'],
            ],
            'mandateDtls' => [
                [
                    'custRefNo'            => '987654321',
                    'requestDate'          => '25 Jul 2019 03:20 PM',
                    'referenceNumber'      => $payment['id'],
                    'txnId'                => '',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'UPDATE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => '',
                    'payerVpa'             => $payment['vpa'],
                    'payerName'            => '',
                    'payeeVpa'             => '',
                    'payeeName'            => '',
                    'status'               => 'ACTIVE',
                    'debitIfsc'            => 'HSBC0001850',
                    'debitAccount'         => '777777777777777',
                    'creditIfsc'           => 'SBIN0000001',
                    'creditAccount'        => '671176176817611',
                    'noOfDebit'            => 0,
                    'remainingDebit'       => 0,
                    'onBehalf_Of'          => 'PAYER',
                    'amt_rule'             => 'EXACT',
                    'has_update_authority' => 'N',
                    'shareToPayee'         => 'Y',
                    'create_date_time'     => '25 Jul 2019 03:20 PM',
                    'show_QR'              => 'Y',
                    'callback_type'        => 'MANDATE_UPDATE',
                    'purpose_code'         => '00',
                    'message'              => 'Mandate updated successfully'
                ]
            ],
        ];

        $jsonResponse =  json_encode($response);

        $content = $this->encrypt($jsonResponse);

        $response = [
            'pgMerchantId' => 'HDFC000006002278',
            'payload'      => $content,
            'type'         => 'mandate_update'
        ];

        return $response;
    }

    protected function getReferenceNumberForCallback($payment, $action)
    {
        $content = [
            $payment['id'],  //'id'
            '0',             //'env'
            $action,         //'act'
            '0',             //'ano'
        ];

        $this->content($content, __FUNCTION__);

        if ($payment['recurring'] === false)
        {
            return $content[0];
        }

        return join($content);
    }

    protected function getHybridEncryptedResponse($response)
    {
        $jsonResponse = json_encode($response);

        $encryptedData = base64_encode($jsonResponse);

        $responseData = [
            'requestId'            => '',
            'service'              => 'UPI',
            'encryptedKey'         => '',
            'oaepHashingAlgorithm' => 'NONE',
            'iv'                   => '',
            'encryptedData'        => $encryptedData,
            'clientInfo'           => '',
            'optionalParam'        => '',
        ];

        return json_encode($responseData);
    }
}
