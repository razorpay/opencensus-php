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

            case Payment\Gateway::UPI_RZPAPB:
                return $this->getAsyncCallbackResponseMandateCreateForApb($payment);

            case Payment\Gateway::UPI_YESBANK:
                return $this->getAsyncCallbackResponseMandateCreateForYesBank($payment);

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

            case Payment\Gateway::UPI_RZPAPB:
                return $this->getAsyncCallbackResponseMandateCreateForApb($payment);

            case Payment\Gateway::UPI_YESBANK:
                return $this->getAsyncCallbackResponseFirstDebitForYesBank($payment);
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

            case Payment\Gateway::UPI_RZPAPB:
                return $this->getAsyncCallbackResponsePauseForApb($mandate);

            case Payment\Gateway::UPI_YESBANK:
                return $this->getAsyncCallbackResponsePauseMandateForYesBank($mandate);

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

            case Payment\Gateway::UPI_RZPAPB:
                return $this->getAsyncCallbackResponseResumeForApb($mandate);

            case Payment\Gateway::UPI_YESBANK:
                return $this->getAsyncCallbackResponseResumeMandateForYesBank($mandate);

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

            case Payment\Gateway::UPI_RZPAPB:
                return $this->getAsyncCallbackResponseRevokeForApb($mandate);

            case Payment\Gateway::UPI_YESBANK:
                return $this->getAsyncCallbackResponseRevokeMandateForYesBank($mandate);

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
                'pgMerchantid'  => $payment->terminal['gateway_merchant_id'],
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

        if($payment["description"] === 'rearch_callback')
        {
            return $response;
        }

        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => $payment->terminal['gateway_merchant_id'],
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseMandateCreateForApb($payment)
    {
        $response = [
            'entity'=> 'upi.mandate',
            'mandate' => true,
            'reference_id'=> $this->getReferenceNumberForCallback($payment, 'create'),
            'upi_transaction_id'=> 'RZPc2ed455b797e4add8392110cfc528acc',
            'upi_customer_reference_number'=> '804813039157',
            'amount'=> 100,
            'amount_rule'=> 'max',
            'block_fund'=> true,
            'created_at'=> 1722317078,
            'currency'=> 'INR',
            'description'=> 'Sample Mandate Entity',
            'expire_at'=> 1722317078,
            'initiated_by'=> 'Payee',
            'name'=> 'mandate name',
            'pause'=> [
                'start'=> '',
                'end'=> ''
            ],
            'merchant'=> [
                'vpa'=> 'swiggy@rzp',
                'name'=> 'Swiggy Pvt. Ltd.',
                'mcc'=> '6765'
            ],
            'payer'=> [
                'vpa'=> '7262093972.stage@rzp'
            ],
            'revocable_by_payer'=> true,
            'recurrence'=> [
                'period'=> 'as_presented',
                'rule'=> 'before',
                'value'=> 31
            ],
            'sequence_number'=> 1,
            'share_to_payee'=> true,
            'status'=> 'active',
            'upi_reference_category'=> '02',
            'upi_reference_url'=> 'https=>//www.abcxyz.com/',
            'upi_initiation_mode'=> '00',
            'upi_purpose_code'=> '14',
            'upi_error_code'=> '14',
            'upi_response_code'=> '00',
            'upi_response_message'=> 'message',
            'validity'=> [
                'start_at'=> 1722317078,
                'end_at'=> 1722317078
            ],
            'umn'=> 'XYZa977ccabb11e7abc4cec278b6b50a@mypsp'
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseMandateCreateForYesBank($payment)
    {
        $response = [
            'requestInfo' => [
                'pgMerchantId' => 'YBL000000088302',
                'pspRefNo' => $this->getReferenceNumberForCallback($payment, 'create'),
            ],
            'mandateDtls' => [
                [
                    'custRefNo' => '024009044860',
                    'mandate' => true,
                    'requestDate' => '07 Feb 2024 05:59 PM',
                    'referenceNumber' => $this->getReferenceNumberForCallback($payment, 'create'),
                    'txnId' => 'HMUO9LQS7RO0JEC165ORS4KDRUEW6GKWWW',
                    'remarks' => 'UPI Mandate',
                    'name' => 'CREATE Mandate test',
                    'mandateType' => 'CREATE',
                    'frequency' => 'MONTHLY',
                    'amount' => '12',
                    'startDate' => '07 Feb 2024',
                    'endDate' => '07 Jun 2024',
                    'UMN' => '9007343d2f2448babcabd7e080a87e0f@yesu',
                    'payerVPA' => '7262093972.stage@rzp',
                    'payerName' => 'Prashant',
                    'payeeVPA' => 'insolglob@yes',
                    'payeeName' => 'InSolutionGloabl',
                    'status' => 'ACTIVE',
                    'statusDesc' => 'Request Processed Successfully',
                    'debitIfsc' => 'MGAT0560004',
                    'debitAccount' => '001002003124',
                    'creditIfsc' => 'MGAT0400002',
                    'crediAccount' => '4111111111111111',
                    'mndregrefno' => 4210001,
                    'noOfDebit' => '5',
                    'onBehalf_Of' => 'PAYEE',
                    'amt_rule' => 'EXACT',
                    'ruleType' => 'ON',
                    'ruleValue' => 4,
                    'has_update_authority' => 'N',
                    'create_date_time' => '07 Feb 2024 05:59 PM',
                    'ref_url' => 'https://www.mgs.co.in',
                    'errCode' => '00',
                    'respCode' => '00',
                    'payType' => 'P2M',
                    'show_QR' => 'N',
                    'callback_type' => 'MANDATE_STATUS',
                    'purpose_code' => '14',
                    'initiationMode' => '01',
                    'merchantType' => 'SMALL',
                    'message' => 'APPROVED OR COMPLETED SUCCESSFULLY',
                    'is_verified' => 'false',
                    'blockFund' => 'N',
                    'initiatedBy' => 'PAYEE',
                    'nextRecurDate' => 'Mar 07, 2024 12:00:00 AM',
                    'remRecuCount' => '1',
                    'pauseStartDate' => ' ',
                    'pauseEndDate' => ' ',
                    'pydMobile' => '919999900099',
                    'orgTxnId' => 'MGA6673417D738B49E58C9F74366C598214',
                    'voucher_UUID' => 'insolglob@yes',
                    'debitAccountType' => 'SAVINGS'
                ]
            ]
        ];
        return json_encode($response);
    }

    protected function getAsyncCallbackResponseFirstDebitForApb($payment)
    {
        $response = [
            'entity'=> 'upi.mandate_execution',
            'mandate' => true,
            'reference_id'=> $this->getReferenceNumberForCallback($payment, 'execte'),
            'upi_transaction_id'=> 'RZP1KuSUGrp2l6MmPuT0163789452QPAY02',
            'upi_customer_reference_number'=> '804813039157',
            'umn'=> '130a977ccabb11e7abc4cec278b6b50a@mypsp',
            'description'=> 'Sample Mandate Execution Request',
            'amount'=> 100,
            'sequence_number'=> 1,
            'status'=> 'success',
            'upi_error_code'=> '00',
            'upi_payer_response_code'=> '00',
            'upi_payer_reversal_response_code'=> '00',
            'upi_payee_response_code'=> '00',
            'upi_payee_reversal_response_code'=> '00',
            'upi_response_message'=> 'message',
            'expire_at'=> 1722317078,
            'payer'=> [
                'vpa'=> 'rohit@rzp',
                'fundsource'=> [
                    'type'=> 'savings',
                    'ifsc'=> 'AXIS0000058',
                    'masked_account_number'=> 'XXXXXXXXXXX3000'
                ],
                'name'=> 'Rohit Sharma'
            ]
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseFirstDebitForYesBank($payment)
    {
        $response = [
            'requestInfo' => [
                'pgMerchantId' => 'YBL000000088302',
                'pspRefNo' => $this->getReferenceNumberForCallback($payment, 'execte'),
            ],
            'mandateDtls' => [
                [
                    'txnId' => 'MGA2B2515B2CEF1409C84A4FD4D4EBB9DA6',
                    'mandate' => true,
                    'custRefNo' => '024009044860',
                    'amount' => '27',
                    'status' => 'SUCCESS',
                    'statusDesc' => 'Transaction success',
                    'payerVPA' => 'saurav2828@yesu',
                    'payeeVPA' => 'merchant@mybank',
                    'payerName' => 'saurav',
                    'payeeName' => 'Merchant',
                    'requestDate' => '28 Feb 2024 12:42 PM',
                    'remarks' => 'UPI Mandate',
                    'name' => 'CREATE Mandate test',
                    'startDate' => '28 Feb 2024',
                    'endDate' => '18 Jul 2024',
                    'isRevokeable' => 'Y',
                    'noOfDebit' => '5',
                    'onBehalf_Of' => 'PAYEE',
                    'create_date_time' => '28 Feb 2024 12:42 PM',
                    'show_QR' => 'N',
                    'ref_url' => 'https://www.upi.com',
                    'callback_type' => 'MANDATE_EXCECUTION',
                    'purpose_code' => '14',
                    'message' => 'Recurrence Payment Success',
                    'is_verified' => 'false',
                    'blockFund' => 'N',
                    'initiatedBy' => 'PAYEE',
                    'nextRecurDate' => '27 Feb 2024',
                    'remRecuCount' => '1',
                    'frequency' => 'MONTHLY',
                    'UMN' => '9007343d2f2448babcabd7e080a87e0f@yesu',
                    'amt_rule' => 'MAX',
                    'payType' => 'P2M',
                    'merchantType' => 'SMALL',
                    'pydMobile' => '919999900099',
                    'referenceNumber' => $this->getReferenceNumberForCallback($payment, 'execte'),
                    'debitIfsc' => 'MGAT0560004',
                    'debitAccount' => '001002003124',
                    'creditIfsc' => 'MGAT0400002',
                    'crediAccount' => '4111111111111111',
                    'orgTxnId' => 'MGA6673417D738B49E58C9F74366C598214',
                    'refId' => 'ABCDHEJF78X616WO2FQKYG4KI16XXDBBb',
                    'respCode' => '00',
                    'errCode' => '00'
                ]
            ]
        ];
        return json_encode($response);
    }

    protected function getAsyncCallbackResponsePauseMandateForYesBank($mandate)
    {
        $response = [
            'requestInfo' => [
                'pgMerchantId' => 'YBL000000088302',
                'pspRefNo' => $mandate['id'],
            ],
            'mandateDtls' => [
                [
                    'custRefNo' => '024009044860',
                    'mandate' => true,
                    'requestDate' => '07 Feb 2024 05:59 PM',
                    'referenceNumber' => $mandate['id'],
                    'txnId' => 'HMUO9LQS7RO0JEC165ORS4KDRUEW6GKWWW',
                    'remarks' => 'UPI Mandate',
                    'name' => 'CREATE Mandate test',
                    'mandateType' => 'CREATE',
                    'frequency' => 'MONTHLY',
                    'amount' => '12',
                    'startDate' => '07 Feb 2024',
                    'endDate' => '07 Jun 2024',
                    'UMN' => $mandate['umn'],
                    'payerVPA' => '7262093972.stage@rzp',
                    'payerName' => 'Prashant',
                    'payeeVPA' => 'insolglob@yes',
                    'payeeName' => 'InSolutionGloabl',
                    'status' => 'PAUSE',
                    'statusDesc' => 'Request Processed Successfully',
                    'debitIfsc' => 'MGAT0560004',
                    'debitAccount' => '001002003124',
                    'creditIfsc' => 'MGAT0400002',
                    'crediAccount' => '4111111111111111',
                    'mndregrefno' => 4210001,
                    'noOfDebit' => '5',
                    'onBehalf_Of' => 'PAYEE',
                    'amt_rule' => 'EXACT',
                    'ruleType' => 'ON',
                    'ruleValue' => 4,
                    'has_update_authority' => 'N',
                    'create_date_time' => '07 Feb 2024 05:59 PM',
                    'ref_url' => 'https://www.mgs.co.in',
                    'errCode' => '00',
                    'respCode' => '00',
                    'payType' => 'P2M',
                    'show_QR' => 'N',
                    'callback_type' => 'MANDATE_STATUS',
                    'purpose_code' => '14',
                    'initiationMode' => '01',
                    'merchantType' => 'SMALL',
                    'message' => 'APPROVED OR COMPLETED SUCCESSFULLY',
                    'is_verified' => 'false',
                    'blockFund' => 'N',
                    'initiatedBy' => 'PAYEE',
                    'nextRecurDate' => 'Mar 07, 2024 12:00:00 AM',
                    'remRecuCount' => '1',
                    'pauseStartDate' => ' ',
                    'pauseEndDate' => ' ',
                    'pydMobile' => '919999900099',
                    'orgTxnId' => 'MGA6673417D738B49E58C9F74366C598214',
                    'voucher_UUID' => 'insolglob@yes',
                    'debitAccountType' => 'SAVINGS'
                ]
            ]
        ];
        return json_encode($response);
    }

    protected function getAsyncCallbackResponseResumeMandateForYesBank($mandate)
    {
        $response = [
            'requestInfo' => [
                'pgMerchantId' => 'YBL000000088302',
                'pspRefNo' => $mandate['id'],
            ],
            'mandateDtls' => [
                [
                    'custRefNo' => '024009044860',
                    'mandate' => true,
                    'requestDate' => '07 Feb 2024 05:59 PM',
                    'referenceNumber' => $mandate['id'],
                    'txnId' => 'HMUO9LQS7RO0JEC165ORS4KDRUEW6GKWWW',
                    'remarks' => 'UPI Mandate',
                    'name' => 'CREATE Mandate test',
                    'mandateType' => 'CREATE',
                    'frequency' => 'MONTHLY',
                    'amount' => '12',
                    'startDate' => '07 Feb 2024',
                    'endDate' => '07 Jun 2024',
                    'UMN' => $mandate['umn'],
                    'payerVPA' => '7262093972.stage@rzp',
                    'payerName' => 'Prashant',
                    'payeeVPA' => 'insolglob@yes',
                    'payeeName' => 'InSolutionGloabl',
                    'status' => 'UNPAUSE',
                    'statusDesc' => 'Request Processed Successfully',
                    'debitIfsc' => 'MGAT0560004',
                    'debitAccount' => '001002003124',
                    'creditIfsc' => 'MGAT0400002',
                    'crediAccount' => '4111111111111111',
                    'mndregrefno' => 4210001,
                    'noOfDebit' => '5',
                    'onBehalf_Of' => 'PAYEE',
                    'amt_rule' => 'EXACT',
                    'ruleType' => 'ON',
                    'ruleValue' => 4,
                    'has_update_authority' => 'N',
                    'create_date_time' => '07 Feb 2024 05:59 PM',
                    'ref_url' => 'https://www.mgs.co.in',
                    'errCode' => '00',
                    'respCode' => '00',
                    'payType' => 'P2M',
                    'show_QR' => 'N',
                    'callback_type' => 'MANDATE_STATUS',
                    'purpose_code' => '14',
                    'initiationMode' => '01',
                    'merchantType' => 'SMALL',
                    'message' => 'APPROVED OR COMPLETED SUCCESSFULLY',
                    'is_verified' => 'false',
                    'blockFund' => 'N',
                    'initiatedBy' => 'PAYEE',
                    'nextRecurDate' => 'Mar 07, 2024 12:00:00 AM',
                    'remRecuCount' => '1',
                    'pauseStartDate' => ' ',
                    'pauseEndDate' => ' ',
                    'pydMobile' => '919999900099',
                    'orgTxnId' => 'MGA6673417D738B49E58C9F74366C598214',
                    'voucher_UUID' => 'insolglob@yes',
                    'debitAccountType' => 'SAVINGS'
                ]
            ]
        ];
        return json_encode($response);
    }

    protected function getAsyncCallbackResponseRevokeMandateForYesBank($mandate)
    {
        $response = [
            'requestInfo' => [
                'pgMerchantId' => 'YBL000000088302',
                'pspRefNo' => $mandate['id'],
            ],
            'mandateDtls' => [
                [
                    'custRefNo' => '024009044860',
                    'mandate' => true,
                    'requestDate' => '07 Feb 2024 05:59 PM',
                    'referenceNumber' => $mandate['id'],
                    'txnId' => 'HMUO9LQS7RO0JEC165ORS4KDRUEW6GKWWW',
                    'remarks' => 'UPI Mandate',
                    'name' => 'CREATE Mandate test',
                    'mandateType' => 'CREATE',
                    'frequency' => 'MONTHLY',
                    'amount' => '12',
                    'startDate' => '07 Feb 2024',
                    'endDate' => '07 Jun 2024',
                    'UMN' => $mandate['umn'],
                    'payerVPA' => '7262093972.stage@rzp',
                    'payerName' => 'Prashant',
                    'payeeVPA' => 'insolglob@yes',
                    'payeeName' => 'InSolutionGloabl',
                    'status' => 'REVOKED',
                    'statusDesc' => 'Request Processed Successfully',
                    'debitIfsc' => 'MGAT0560004',
                    'debitAccount' => '001002003124',
                    'creditIfsc' => 'MGAT0400002',
                    'crediAccount' => '4111111111111111',
                    'mndregrefno' => 4210001,
                    'noOfDebit' => '5',
                    'onBehalf_Of' => 'PAYEE',
                    'amt_rule' => 'EXACT',
                    'ruleType' => 'ON',
                    'ruleValue' => 4,
                    'has_update_authority' => 'N',
                    'create_date_time' => '07 Feb 2024 05:59 PM',
                    'ref_url' => 'https://www.mgs.co.in',
                    'errCode' => '00',
                    'respCode' => '00',
                    'payType' => 'P2M',
                    'show_QR' => 'N',
                    'callback_type' => 'MANDATE_STATUS',
                    'purpose_code' => '14',
                    'initiationMode' => '01',
                    'merchantType' => 'SMALL',
                    'message' => 'APPROVED OR COMPLETED SUCCESSFULLY',
                    'is_verified' => 'false',
                    'blockFund' => 'N',
                    'initiatedBy' => 'PAYEE',
                    'nextRecurDate' => 'Mar 07, 2024 12:00:00 AM',
                    'remRecuCount' => '1',
                    'pauseStartDate' => ' ',
                    'pauseEndDate' => ' ',
                    'pydMobile' => '919999900099',
                    'orgTxnId' => 'MGA6673417D738B49E58C9F74366C598214',
                    'voucher_UUID' => 'insolglob@yes',
                    'debitAccountType' => 'SAVINGS'
                ]
            ]
        ];
        return json_encode($response);
    }

    protected function getAsyncCallbackResponseRevokeForApb($mandate)
    {
        $response = [
            'entity'=> 'upi.mandate',
            'mandate' => true,
            'upi_transaction_id'=> 'RZPc2ed455b797e4add8392110cfc528acc',
            'upi_customer_reference_number'=> '804813039157',
            'amount'=> 100,
            'amount_rule'=> 'max',
            'block_fund'=> true,
            'created_at'=> 1722317078,
            'currency'=> 'INR',
            'description'=> 'Sample Mandate Entity',
            'expire_at'=> 1722317078,
            'initiated_by'=> 'Payee',
            'name'=> 'mandate name',
            'pause'=> [
                'start'=> '',
                'end'=> ''
            ],
            'merchant'=> [
                'vpa'=> 'swiggy@rzp',
                'name'=> 'Swiggy Pvt. Ltd.',
                'mcc'=> '6765'
            ],
            'payer'=> [
                'vpa'=> '7262093972.stage@rzp'
            ],
            'revocable_by_payer'=> true,
            'recurrence'=> [
                'period'=> 'as_presented',
                'rule'=> 'before',
                'value'=> 31
            ],
            'sequence_number'=> 1,
            'share_to_payee'=> true,
            'status'=> 'revoked',
            'upi_reference_category'=> '02',
            'upi_reference_url'=> 'https=>//www.abcxyz.com/',
            'upi_initiation_mode'=> '00',
            'upi_purpose_code'=> '14',
            'upi_error_code'=> '14',
            'upi_response_code'=> '00',
            'upi_response_message'=> 'message',
            'validity'=> [
                'start_at'=> 1722317078,
                'end_at'=> 1722317078
            ],
            'umn'=> $mandate['umn']
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponsePauseForApb($mandate)
    {
        $response = [
            'entity'=> 'upi.mandate',
            'mandate' => true,
            'upi_transaction_id'=> 'RZPc2ed455b797e4add8392110cfc528acc',
            'upi_customer_reference_number'=> '804813039157',
            'amount'=> 100,
            'amount_rule'=> 'max',
            'block_fund'=> true,
            'created_at'=> 1722317078,
            'currency'=> 'INR',
            'description'=> 'Sample Mandate Entity',
            'expire_at'=> 1722317078,
            'initiated_by'=> 'Payee',
            'name'=> 'mandate name',
            'pause'=> [
                'start'=> '',
                'end'=> ''
            ],
            'merchant'=> [
                'vpa'=> 'swiggy@rzp',
                'name'=> 'Swiggy Pvt. Ltd.',
                'mcc'=> '6765'
            ],
            'payer'=> [
                'vpa'=> '7262093972.stage@rzp'
            ],
            'revocable_by_payer'=> true,
            'recurrence'=> [
                'period'=> 'as_presented',
                'rule'=> 'before',
                'value'=> 31
            ],
            'sequence_number'=> 1,
            'share_to_payee'=> true,
            'status'=> 'paused',
            'upi_reference_category'=> '02',
            'upi_reference_url'=> 'https=>//www.abcxyz.com/',
            'upi_initiation_mode'=> '00',
            'upi_purpose_code'=> '14',
            'upi_error_code'=> '14',
            'upi_response_code'=> '00',
            'upi_response_message'=> 'message',
            'validity'=> [
                'start_at'=> 1722317078,
                'end_at'=> 1722317078
            ],
            'umn'=> $mandate['umn']
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseResumeForApb($mandate)
    {
        $response = [
            'entity'=> 'upi.mandate',
            "mandate" => true,
            'upi_transaction_id'=> 'RZPc2ed455b797e4add8392110cfc528acc',
            'upi_customer_reference_number'=> '804813039157',
            'amount'=> 100,
            'amount_rule'=> 'max',
            'block_fund'=> true,
            'created_at'=> 1722317078,
            'currency'=> 'INR',
            'description'=> 'Sample Mandate Entity',
            'expire_at'=> 1722317078,
            'initiated_by'=> 'Payee',
            'name'=> 'mandate name',
            'pause'=> [
                'start'=> '',
                'end'=> ''
            ],
            'merchant'=> [
                'vpa'=> 'swiggy@rzp',
                'name'=> 'Swiggy Pvt. Ltd.',
                'mcc'=> '6765'
            ],
            'payer'=> [
                'vpa'=> '7262093972.stage@rzp'
            ],
            'revocable_by_payer'=> true,
            'recurrence'=> [
                'period'=> 'as_presented',
                'rule'=> 'before',
                'value'=> 31
            ],
            'sequence_number'=> 1,
            'share_to_payee'=> true,
            'status'=> 'unpaused',
            'upi_reference_category'=> '02',
            'upi_reference_url'=> 'https=>//www.abcxyz.com/',
            'upi_initiation_mode'=> '00',
            'upi_purpose_code'=> '14',
            'upi_error_code'=> '14',
            'upi_response_code'=> '00',
            'upi_response_message'=> 'message',
            'validity'=> [
                'start_at'=> 1722317078,
                'end_at'=> 1722317078
            ],
            'umn'=> $mandate['umn']
        ];

        return json_encode($response);
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
                'pgMerchantid'  => $payment->terminal['gateway_merchant_id'],
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
                    'message'              => 'Initial debit successful',
                    'respCode'             => '00'
                ]
            ],
        ];

        if($payment["description"] === 'rearch_callback')
        {
            return $response;
        }

        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => $payment->terminal['gateway_merchant_id'],
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
        ];

        return json_encode($response);
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
                'pgMerchantid'  => 'abcd',
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
                    'status'               => 'REVOKED',
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

        if($mandate["description"] === 'rearch_callback')
        {
            return $response;
        }

        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => 'abcd',
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponsePauseForMindgate($mandate)
    {
        print_r($mandate->token->terminal['gateway_merchant_id']);

        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'abcd',
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
                    'mandateType'          => 'PAUSE',
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

        if($mandate["description"] === 'rearch_callback')
        {
            return $response;
        }

        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => 'abcd',
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
        ];

        return json_encode($response);
    }

    protected function getAsyncCallbackResponseResumeForMindgate($mandate)
    {
        $response = [
            'call_back_id'  => '1234',
            'requestInfo'   => [
                'pgMerchantid'  => 'abcd',
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
                    'mandateType'          => 'UNPAUSE',
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

        if($mandate["description"] === 'rearch_callback')
        {
            return $response;
        }

        $jsonResponse =  json_encode($response);

        $iv = strtoupper(bin2hex(random_bytes(16)));

        $content = $this->encryptForMandate($jsonResponse, $iv);

        $response = [
            'pgMerchantId' => 'abcd',
            'payload'      => $content,
            'ivToken'      => $iv,
            'keyId'        => 1
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
            'requestType' => 'UNPAUSE',
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
