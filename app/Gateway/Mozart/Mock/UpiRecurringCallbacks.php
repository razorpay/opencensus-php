<?php

namespace RZP\Gateway\Mozart\Mock;

use Str;
use RZP\App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use phpseclib\Crypt\AES;
use RZP\Models\UpiMandate;
use RZP\Gateway\Upi\Axis\Mock\AESCrypto;

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

            case Payment\Gateway::UPI_AXIS:
                return $this->getAsyncCallbackResponseMandateCreateForAxis($payment);

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

            case Payment\Gateway::UPI_AXIS:
                return $this->getAsyncCallbackResponseFirstDebitForAxis($payment);

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

            case Payment\Gateway::UPI_AXIS:
                return $this->getAsyncCallbackResponsePauseForAxis($mandate);

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

            case Payment\Gateway::UPI_AXIS:
                return $this->getAsyncCallbackResponseResumeForAxis($mandate);

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

            case Payment\Gateway::UPI_AXIS:
                return $this->getAsyncCallbackResponseRevokeForAxis($mandate);

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
                    'txnId'                => 'HDFC00001124',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'CREATE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => '12121jjberbnvejrgufwebjw@icici',
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
                    'message'              => 'APPROVED OR COMPLETED SUCCESSFULLY',
                    'respCode'             => '00'
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
                    'message'              => 'Initial debit successful'
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

    protected function getAsyncCallbackResponseRevokeForMindgate($mandate)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'HDFC000006002278',
                'pspRefNo'      =>  '1211121212',
            ],
            'mandateDtls' => [
                [
                    'custRefNo'            => '987654321',
                    'requestDate'          => '25 Jul 2019 03:20 PM',
                    'referenceNumber'      => '1211121212',
                    'txnId'                => '',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'REVOKE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => $mandate['umn'],
                    'payerVpa'             => '',
                    'payerName'            => '',
                    'payeeVpa'             => '',
                    'payeeName'            => '',
                    'status'               => 'REVOKE',
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
                    'message'              => 'Mandate revoked successfully'
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

    protected function getAsyncCallbackResponsePauseForMindgate($mandate)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'HDFC000006002278',
                'pspRefNo'      =>  '12112121',
            ],
            'mandateDtls' => [
                [
                    'custRefNo'            => '987654321',
                    'requestDate'          => '25 Jul 2019 03:20 PM',
                    'referenceNumber'      => '111112121',
                    'txnId'                => '',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'UPDATE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => $mandate['umn'],
                    'payerVpa'             => '',
                    'payerName'            => '',
                    'payeeVpa'             => '',
                    'payeeName'            => '',
                    'status'               => 'PAUSE',
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
                    'message'              => 'Mandate paused successfully'
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

    protected function getAsyncCallbackResponseResumeForMindgate($mandate)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'HDFC000006002278',
                'pspRefNo'      =>  '12112121',
            ],
            'mandateDtls' => [
                [
                    'custRefNo'            => '987654321',
                    'requestDate'          => '25 Jul 2019 03:20 PM',
                    'referenceNumber'      => '111112121',
                    'txnId'                => '',
                    'remarks'              => '',
                    'name'                 => '',
                    'mandateType'          => 'UPDATE',
                    'amount'               => '20.00',
                    'startDate'            => '25 July 2019',
                    'endDate'              => '26 July 2019',
                    'UMN'                  => $mandate['umn'],
                    'payerVpa'             => '',
                    'payerName'            => '',
                    'payeeVpa'             => '',
                    'payeeName'            => '',
                    'status'               => 'UNPAUSE',
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
                    'message'              => 'Mandate resumed successfully'
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

    protected function getAsyncCallbackResponseMandateCreateForAxis($payment)
    {
        $response = [
            'transactionId' => $this->getReferenceNumberForCallback($payment, 'create'),
            'status' => 'SUCCESS',
            'responsecode' => '00',
            'requestType' => 'CREATE',
            'amountRule' => 'MAX',
            'amountrulevalue' => '100.00',
            'umn' => 'AXIebdef01e51b340448dd0caf01fffc@axis',
            'payerAddr' => $payment['vpa'],
            'payeeAddr' => 'payu@axis',
            'validityStartTime' => '30122019',
            'validityEndTime' => '01012020',
            'recurrence' => 'ASPRESENTED',
            'createdate' => '30-12-2019 11:11:11',
            'updatedate' => '30-12-2019 11:11:11',
            'rrn' => '01234567891',
            'payeeAccountRefNumber' => '566802070000181',
            'payeeAccountIfsc' => 'AXIS0012345',
            'payerAccountRefNumber' => '566802070000181',
            'payerAccountIfsc' => 'AXIS0012345',
            'checksum' => '2D298AF65C53E74C7FE2DBEDAC541DE8FA8059A81306FAA234553480ED669EFD579448FBB1806A333FE249AE4E66DE85529F45C72D10907060791D50BA17FAD23D9A41774D2C46D09C1F89C26CE3547B7F6105F3DAFE64F9CF64150A795C443565C80B9F5BE67C84A4356905E266481A1985E53685E278B75593432A9169764A9F2B947CB12C11D5272004BCD69E8D2DAD513F2FFE9F819D442E855CEB66B47572748482AC9BD5FCA17886C5304195C6EF004E1FE0B5622088483D9611ABDB41032420C43CC9F3E527AE8D5C640C36279336D4100646CAC748D24D9F950DFD5BB24E0617BCF2DF3537E8DBDB9F3C74D772C98DE5EAE3AB5044BA38A121BD3ADF'
        ];

        $this->content($response,'callback');

        $json = json_encode($response);

        $aesencrypted = $this->encryptAes($json);

        return [
            'data' => $aesencrypted
        ];
    }

    protected function getAsyncCallbackResponseFirstDebitForAxis($payment)
    {
        $response = [
            'transactionId' => $this->getReferenceNumberForCallback($payment, 'execte'),
            'status' => 'SUCCESS',
            'responsecode' => '00',
            'requestType' => 'EXECUTE',
            'amountRule' => 'MAX',
            'amountrulevalue' => '100.00',
            'umn' => 'AXIebdef01e51b340448dd0caf01fffc@axis',
            'payerAddr' => $payment['vpa'],
            'payeeAddr' => 'payu@axis',
            'validityStartTime' => '30122019',
            'validityEndTime' => '01012020',
            'recurrence' => 'ONETIME',
            'createdate' => '30-12-2019 11:11:11',
            'updatedate' => '30-12-2019 11:11:11',
            'executionDate' => '27-05-2020 11:11:11',
            'rrn' => '01234567891',
            'payeeAccountRefNumber' => '566802070000181',
            'payeeAccountIfsc' => 'AXIS0012345',
            'payerAccountRefNumber' => '566802070000181',
            'payerAccountIfsc' => 'AXIS0012345',
            'checksum' => '2D298AF65C53E74C7FE2DBEDAC541DE8FA8059A81306FAA234553480ED669EFD579448FBB1806A333FE249 AE4E66DE85529F45C72D10907060791D50BA17FAD23D9A41774D2C46D09C1F89C26CE3547B7F6105F3 DAFE64F9CF64150A795C443565C80B9F5BE67C84A4356905E266481A1985E53685E278B75593432A916 9764A9F2B947CB12C11D5272004BCD69E8D2DAD513F2FFE9F819D442E855CEB66B47572748482AC9B D5FCA17886C5304195C6EF004E1FE0B5622088483D9611ABDB41032420C43CC9F3E527AE8D5C640C36 279336D4100646CAC748D24D9F950DFD5BB24E0617BCF2DF3537E8DBDB9F3C74D772C98DE5EAE3AB5 044BA38A121BD3ADF'
        ];

        $this->content($response,'callback');

        $json = json_encode($response);

        $aesencrypted = $this->encryptAes($json);

        return [
            'data' => $aesencrypted
        ];
    }

    protected function getAsyncCallbackResponsePauseForAxis($mandate)
    {
        $response = [
            'transactionId' => 'creta12345673221323',
            'status' => 'SUCCESS',
            'responsecode' => '00',
            'requestType' => 'PAUSE',
            'amountrulevalue' => '31.00',
            'umn' => $mandate['umn'],
            'payerAddr' => '9826083167@upi',
            'payeeAddr' => 'payu@axis',
            ' validityStartTime' => '27082020',
            'validityEndTime' => '29082020',
            'recurrence' => 'DAILY',
            'createdate' => '27-08-2020 12:51:08',
            'rrn' => '024013565623',
            'payeeAccountIfsc' => 'AXIS0000447',
            'payeeAccountRefNumber' => '914020008517780',
            'checksum' => '608ED96F9FCD000B76A882999864C994B6F865EC8B883363082A5F486653DF1DDA083D3F4EA12B31B22A2C4B92CD0FEFD13CA3DC2F75BC17FEC533C7276F8EC1DAE75EDD0C39932AB13D1D38FC5E35FB5299C32AD8AC2D3A648AF779F215A846CAB0F18607C0826D486D8ABF11B8446E4E3D3EFC51820D868AB0DEA330377F59C5896BD8A381D5F23B6337DB4DD89900B1841D17A4A7399D1D38DCA5A5BAAF65670E3C2C4046058923A471BD531A8D7E135A6178CE5C81ADBF4A010EDF266FA0CBF7A065E127C7F293FFD59FA6E6387301BD352C2949A04F20238951BE52166F25A8A92156C109FBDE9923D986E0787AB90CCB97E0D3F9B8E2703E46A6B3A2D1'
        ];

        $this->content($response,'callback');

        $json = json_encode($response);

        $aesencrypted = $this->encryptAes($json);

        return [
            'data' => $aesencrypted
        ];
    }

    protected function getAsyncCallbackResponseResumeForAxis($mandate)
    {
        $response = [
            'transactionId' => 'creta12345673221323',
            'status' => 'SUCCESS',
            'responsecode' => '00',
            'requestType' => 'RESUME',
            'amountrulevalue' => '31.00',
            'umn' => $mandate['umn'],
            'payerAddr' => '9826083167@upi',
            'payeeAddr' => 'payu@axis',
            ' validityStartTime' => '27082020',
            'validityEndTime' => '29082020',
            'recurrence' => 'DAILY',
            'createdate' => '27-08-2020 12:51:08',
            'rrn' => '024013565623',
            'payeeAccountIfsc' => 'AXIS0000447',
            'payeeAccountRefNumber' => '914020008517780',
            'checksum' => '608ED96F9FCD000B76A882999864C994B6F865EC8B883363082A5F486653DF1DDA083D3F4EA12B31B22A2C4B92CD0FEFD13CA3DC2F75BC17FEC533C7276F8EC1DAE75EDD0C39932AB13D1D38FC5E35FB5299C32AD8AC2D3A648AF779F215A846CAB0F18607C0826D486D8ABF11B8446E4E3D3EFC51820D868AB0DEA330377F59C5896BD8A381D5F23B6337DB4DD89900B1841D17A4A7399D1D38DCA5A5BAAF65670E3C2C4046058923A471BD531A8D7E135A6178CE5C81ADBF4A010EDF266FA0CBF7A065E127C7F293FFD59FA6E6387301BD352C2949A04F20238951BE52166F25A8A92156C109FBDE9923D986E0787AB90CCB97E0D3F9B8E2703E46A6B3A2D1'
        ];

        $this->content($response,'callback');

        $json = json_encode($response);

        $aesencrypted = $this->encryptAes($json);

        return [
            'data' => $aesencrypted
        ];
    }

    protected function getAsyncCallbackResponseRevokeForAxis($mandate)
    {
        $response = [
            'transactionId' => 'creta12345673221323',
            'status' => 'SUCCESS',
            'responsecode' => '00',
            'requestType' => 'REVOKE',
            'amountrulevalue' => '31.00',
            'umn' => $mandate['umn'],
            'payerAddr' => '9826083167@upi',
            'payeeAddr' => 'payu@axis',
            ' validityStartTime' => '27082020',
            'validityEndTime' => '29082020',
            'recurrence' => 'DAILY',
            'createdate' => '27-08-2020 12:51:08',
            'rrn' => '024013565623',
            'payeeAccountIfsc' => 'AXIS0000447',
            'payeeAccountRefNumber' => '914020008517780',
            'checksum' => '608ED96F9FCD000B76A882999864C994B6F865EC8B883363082A5F486653DF1DDA083D3F4EA12B31B22A2C4B92CD0FEFD13CA3DC2F75BC17FEC533C7276F8EC1DAE75EDD0C39932AB13D1D38FC5E35FB5299C32AD8AC2D3A648AF779F215A846CAB0F18607C0826D486D8ABF11B8446E4E3D3EFC51820D868AB0DEA330377F59C5896BD8A381D5F23B6337DB4DD89900B1841D17A4A7399D1D38DCA5A5BAAF65670E3C2C4046058923A471BD531A8D7E135A6178CE5C81ADBF4A010EDF266FA0CBF7A065E127C7F293FFD59FA6E6387301BD352C2949A04F20238951BE52166F25A8A92156C109FBDE9923D986E0787AB90CCB97E0D3F9B8E2703E46A6B3A2D1'
        ];

        $this->content($response,'callback');

        $json = json_encode($response);

        $aesencrypted = $this->encryptAes($json);

        return [
            'data' => $aesencrypted
        ];
    }

    public function encryptAes(string $stringToEncrypt)
    {
        $this->createCryptoIfNotCreated();

        return $this->aesCrypto->encryptString($stringToEncrypt);
    }

    protected function createCryptoIfNotCreated()
    {
        $gateway = $this->app['gateway']->gateway('upi_axis');

        $this->aesCrypto = new AESCrypto(AES::MODE_ECB, $gateway->getSecret());
    }
}
