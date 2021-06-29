<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as RHeadings;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_hdfc';

    protected $bank = 'hdfc';

    protected $tpv;

    protected $sortRequestContent = false;

    protected $fields = [
        'ClientCode',
        'MerchantCode',
        'TxnCurrency',
        'TxnAmount',
        'TxnScAmount',
        'MerchantRefNo',
        'SuccessStatifFlag',
        'FailureStaticFlag',
        'Date',
    ];

    protected $map = [
        'ClientCode'    => 'client_code',
        'MerchantCode'  => 'merchant_code',
        'TxnAmount'     => 'amount',
        'Message'       => 'error_message',
        'BankRefNo'     => 'bank_payment_id',
        'fldSessionNbr' => 'reference1',
        'Date'          => 'date',
        'MerchantRefNo' => 'verification_id',
    ];

    const DISPLAY_DETAILS = 'Y';

    /**
     * @param  array $input
     *
     * @return array
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = array(
            'url' => $this->getUrl('pay'),
            'method' => 'post',
            'content' => $content);

        $traceRequest = $request;

        unset($traceRequest['content'][Fields::REF3], $traceRequest['content'][Fields::CLIENT_ACCOUNT_NUMBER]);

        $this->traceGatewayPaymentRequest($traceRequest, $input);

        return $request;
    }

    /**
     * We receive callback from atom after bank net-banking
     * transaction is complete
     *
     * @param  array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\GatewayErrorException
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $this->validateCallbackChecksum($input);

        if (empty($input['gateway'][Fields::REF1]) === false)
        {
            $this->assertPaymentId($input['payment']['id'], $input['gateway'][Fields::REF1]);
        }
        else
        {
            $this->assertPaymentId($input['payment']['id'], $input['gateway']['MerchRefNo']);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        // For emandate registration request, we set the amount to Rs 1
        if ($this->isFirstRecurringPayment($input) === true)
        {
            if ($input['payment']['amount'] === 0)
            {
                $expectedAmount = number_format(Fields::INIT_AMOUNT, 2, '.', '');
            }
        }

        $actualAmount = number_format($input['gateway']['TxnAmount'], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        unset($input['gateway']['CheckSum']);

        // Unset date because format of date returned is different than what we sent
        unset($input['gateway']['Date']);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $bankRefNo = $input['gateway']['BankRefNo'];
        $message = $input['gateway']['Message'];
        $message = $input['gateway']['Message'] = substr($message, 0, 255);

        $attrs = $this->getMappedAttributes($input['gateway']);
        $attrs['received'] = true;

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        if (($bankRefNo === '') or
            ($message !== ''))
        {
            // Payment fails, throw exception

            $internalErrorCode = \RZP\Gateway\Netbanking\Hdfc\ErrorCode::getHdfcNetbankingErrorCodes($message);

            throw new Exception\GatewayErrorException(
                    $internalErrorCode,
                    '',
                    $message);
        }

        // If callback status was a success, we verify the payment immediately
        $this->verifyCallback($gatewayPayment, $input);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $recurringData = $this->getRecurringData();

            $acquirerData = array_merge($acquirerData, $recurringData);
        }

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    protected function verifyCallback($gatewayPayment, array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->getGatewayStatus($verify);

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        // For emandate registration request, we set the amount to Rs 1
        if ($this->isFirstRecurringPayment($input) === true)
        {
            if ($input['payment']['amount'] === 0)
            {
                $expectedAmount = number_format(Fields::INIT_AMOUNT, 2, '.', '');
            }
        }

        $actualAmount   = number_format($verify->verifyResponseContent[Fields::TXN_AMOUNT], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function validateCallbackChecksum($input)
    {
        $checksum = $input['gateway']['CheckSum'] ?? null;

        // For an emandate/recurring payment, HDFC doesn't send back checksum
        if (($checksum === null) and
            ($this->isFirstRecurringPayment($input) === true))
        {
            return;
        }

        $expectedChecksum = $this->getCallbackChecksum($input['gateway']);

        if ($checksum !== $expectedChecksum)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getPaymentRequestData($input)
    {
        // Using created_at because the exact same date value will need to be sent for verify request
        $date = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT], Timezone::IST)
                      ->format('d/m/Y H:i:s');

        $clientCode = $this->getClientCode($input);

        $paymentAmount = $input['payment']['amount'];

        $data = [
            'ClientCode'        => $clientCode,
            'MerchantCode'      => $this->getMerchantId(),
            'TxnCurrency'       => 'INR',
            'TxnAmount'         => $paymentAmount / 100,
            'TxnScAmount'       => '0',
            'MerchantRefNo'     => $input['payment']['id'],
            'SuccessStaticFlag' => 'N',
            'FailureStaticFlag' => 'N',
            'Date'              => $date,
        ];

        if ($input['payment']['merchant_id'] === Merchant\Preferences::MID_RELIANCE_AMC)
        {
            $data['MerchantRefNo'] = substr($input['payment']['description'] . '-' . strrev($input['payment']['id']), 0, 19);
        }

        $data[Fields::REF1] = $input['payment']['id'];

        if ($input['merchant']->isTPVRequired())
        {
            $data[Fields::CLIENT_ACCOUNT_NUMBER] = $input['order']['account_number'];

            if ($this->mode === Mode::TEST)
            {
                $data['MerchantCode'] = 'RAZORPAY1';
            }
        }

        if ($this->isFirstRecurringPayment($input) === true)
        {
            //
            // For e mandate registration we have to
            // add the following data in the same sequence
            //

            $token = $input['token'];

            $emData = Fields::getEmandateRegistrationData($token, $input['payment']['id'], $input['merchant']);

            $startDate = Carbon::createFromTimestamp($emData[Fields::START_TIMESTAMP], Timezone::IST);

            $endDate = Carbon::now(Timezone::IST)->setTimestamp($emData[Fields::END_TIMESTAMP]);

            $amount = number_format($token->getMaxAmount() / 100, 2, '.', '');

            $data[Fields::REF1]                  = $emData[RHeadings::MERCHANT_UNIQUE_REFERENCE_NO];
            $data[Fields::REF2]                  = $emData[RHeadings::CUSTOMER_NAME];
            $data[Fields::REF3]                  = $emData[RHeadings::CUSTOMER_ACCOUNT_NUMBER];
            $data[Fields::REF4]                  = $amount;
            $data[Fields::REF5]                  = $emData[RHeadings::FREQUENCY];
            $data[Fields::REF6]                  = $emData[RHeadings::MANDATE_SERIAL_NUMBER];
            $data[Fields::REF7]                  = $emData[RHeadings::MANDATE_ID];
            $data[Fields::REF8]                  = $emData[RHeadings::MERCHANT_REQUEST_NO];
            $data[Fields::REF9]                  = $emData[RHeadings::AMOUNT_TYPE];
            $data[Fields::REF10]                 = $emData[RHeadings::CLIENT_NAME];
            $data[Fields::DATE1]                 = $startDate->format('dmY');
            $data[Fields::DATE2]                 = $endDate->format('dmY');

            //
            // For emandate registration payments, we need to hard-code the amount to Rs 1
            // This payment would be used by HDFC to verify the account details and once
            // verified, the same amount would be refunded to the account holder the next day
            //
            $data['TxnAmount']                   = ($paymentAmount > 0) ? $paymentAmount / 100 : Fields::INIT_AMOUNT;

            $data[Fields::CLIENT_ACCOUNT_NUMBER] = $emData[RHeadings::CUSTOMER_ACCOUNT_NUMBER];

            $data[Fields::DISPLAY_DETAILS]       = self::DISPLAY_DETAILS;

            $data[Fields::DETAILS1]              = Fields::DISPLAY_DEBIT_START_DATE . '~' .
                                                   $startDate->format('d-m-Y') . '|' .
                                                   Fields::DISPLAY_DEBIT_END_DATE . '~' .
                                                   $endDate->format('d-m-Y');

            $data[Fields::DETAILS2]              = Fields::DISPLAY_FREQUENCY . '~' .
                                                   $emData[RHeadings::FREQUENCY] . '|' .
                                                   Fields::DISPLAY_MANDATE_AMOUNT . '~' .
                                                   $amount;

            $data[Fields::DETAILS3]              = Fields::DISPLAY_CUSTOMER_NAME .'~' .
                                                   $emData[RHeadings::CUSTOMER_NAME] . '|' .
                                                   Fields::DISPLAY_MANDATE_ID . '~' .
                                                   $emData[RHeadings::MANDATE_ID];
        }

        // Moving this as the HDFC TPV requires the ClientAccCode to
        // be moved in between the Date and the DynamicUrl
        $data['DynamicUrl'] = $input['callbackUrl'];
        $data['CheckSum'] = $this->generateHash($data);

        return $data;
    }

    protected function isFirstRecurringPayment(array $input): bool
    {
        return ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        // Throw exception as verify is not available for second recurring request
        if (($input['payment']['recurring_type'] === Payment\RecurringType::AUTO) and
            ($input['payment']['recurring'] === true))
        {
            throw new Exception\PaymentVerificationException(
                [], $verify, Payment\Verify\Action::FINISH);
        }

        // Using created_at because this value must match the one that we sent in payment request
        $date = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                      ->format('d/m/Y H:i:s');

        // if (empty($payment['date']) === false)
        // {
        //     // First verify all hdfc netbanking transactions here and
        //     // then remove this in future.
        //     // $date = $payment['date'];
        // }

        $clientCode = $payment['client_code'];

        if ($clientCode === 'client_code')
        {
            $clientCode = $this->getClientCode($input);
        }

        $flgVerify = ($input['payment']['recurring'] === true) ? 'V' : 'Y';

        $txnAmount = $input['payment']['amount'] / 100;

        //
        // For registration request, even though the payment amount is 0,
        // we hard code the amount to 0 to send to the bank.
        //
        if ($this->isFirstRecurringPayment($input))
        {
            $txnAmount = ($input['payment']['amount'] > 0) ? $txnAmount : Fields::INIT_AMOUNT;
        }

        $content = array(
            'MerchantCode'          => $this->getMerchantId(),
            'Date'                  => $date,
            'MerchantRefNo'         => $payment['verification_id'] ?: $payment['payment_id'],
            'TransactionId'         => 'XTXTV01',
            'FlgVerify'             => $flgVerify,
            'ClientCode'            => $clientCode,
            'SuccessStaticFlag'     => 'N',
            'FailureStaticFlag'     => 'N',
            'TxnAmount'             => $txnAmount,
            'Ref1'                  => $payment['payment_id']
        );

        $url = $this->getUrl();

        $request['url'] = $url . '?' . $this->buildQueryString($content);
        $request['method'] = 'get';
        $request['content'] = [];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $content = $this->processContentFromPaymentVerifyResponse($response, $request);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function verifyPayment($verify)
    {

        //
        // In HDFC netbanking, the bank only stores the payment data for
        // 45 days! So for verification requests after 45 days, we simply
        // treat it as successful and return.
        //
        $days = (time() - $verify->input['payment']['created_at']) / (24 * 60 * 60);

        if ($days > 45)
        {
            $this->setVerifyResponseSuccess($verify);

            $status =  VerifyResult::STATUS_MATCH;

            return $status;
        }

        if ($this->getApiStatus($verify) !== $this->getGatewayStatus($verify))
        {
            $status = VerifyResult::STATUS_MISMATCH;

            $verify->match = false;

        }
        else
        {
            $status = VerifyResult::STATUS_MATCH;

            $verify->match = true;

        }

        $this->saveResponseContenttoNetbankingEntity($verify);

        return $status;
    }

    protected function getApiStatus(Verify $verify)
    {
        $payment_status = $verify->input['payment']['status'];

        $verify->apiSuccess = (
            in_array($payment_status, [Payment\Status::CREATED, Payment\Status::FAILED], true) === false
        );

        return $verify->apiSuccess;
    }

    protected function getGatewayStatus(Verify $verify)
    {
        $status = $verify->verifyResponseContent['flgSuccess'];

        $verify->gatewaySuccess = ($status === 'S');

        return $verify->gatewaySuccess;
    }

    protected function saveResponseContenttoNetbankingEntity(Verify $verify)
    {
        if (($verify->payment['received'] === false) or (empty($verify->payment['error_message']) === false))
        {
            $attrs = $this->getMappedAttributes($verify->verifyResponseContent);

            $gatewayPayment = $verify->payment;

            $gatewayPayment->fill($attrs);

            return $gatewayPayment->saveOrFail();
        }

        return false;
    }

    protected function setVerifyResponseSuccess($verify)
    {
        $verify->apiSuccess = true;

        $verify->gatewaySuccess = true;

        $verify->match = true;

        $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'message' => 'In HDFC netbanking, the bank only stores the payment data for 45 days!' .
                        ' Since it has been more than 45 days, we simply treat it as successful and return.',
                    'payment_id' => $verify->input['payment']['id']
                ]);
    }

    protected function processContentFromPaymentVerifyResponse($response, $request)
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [$response->body]);

        $url = null;

        $values = $this->getFormValues($response->body, $request['url']);

        $url = $values['REDIRECTURL'];

        $content = [];
        $parts = parse_url($url);
        parse_str($parts['query'], $content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [$content]
        );

        return $content;
    }

    protected function getCallbackChecksum($input)
    {
        $paramsOrder = array(
            'ClientCode',
            'MerchantCode',
            'TxnCurrency',
            'TxnAmount',
            'TxnScAmount',
            'MerchRefNo',
            'StSucFlg',
            'StFailFlg',
            'Date',
            'Ref1',
            'Ref2',
            'Ref3',
            'Ref4',
            'Ref5',
            'Ref6',
            'Ref7',
            'Ref8',
            'Ref9',
            'Ref10',
            'Ref11',
            'Date1',
            'Date2',
            'BankRefNo',
            'Message',
        );

        $str = '';

        $data = [];

        foreach ($paramsOrder as $param)
        {
            if (isset($input[$param]))
            {
                $data[$param] = $input[$param];
                $str .= $input[$param];
            }
        }

        return $this->getHashOfString($str);
    }

    protected function getClientCode(array $input): string
    {
        $email = $input['payment'][Payment\Entity::EMAIL] ?: Payment\Entity::DUMMY_EMAIL;

        $clientCode = str_limit($this->stripEmailSpecialChars($email), 40, '');

        return $clientCode;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        $body = $response->body;

        $msg = 'Unable to reach destination.';

        if (strpos($body, $msg) !== false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_FAILED);
        }

        return $response;
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return (string) crc32($str . $secret);
    }

    protected function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getLiveSecret()
    {
        assertTrue ($this->mode === Mode::LIVE);

        if ($this->tpv === true)
        {
            return $this->config['live_hash_secret_tpv'];
        }
        else if (isset($this->input['merchant']))
        {
            if ($this->input['merchant']->isTPVRequired())
            {
                return $this->config['live_hash_secret_tpv'];
            }
        }

        return $this->config['live_hash_secret'];
    }

    protected function getTestSecret()
    {
        assertTrue ($this->mode === Mode::TEST);

        if ($this->tpv === true)
        {
            return $this->config['test_hash_secret_tpv'];
        }
        else if (isset($this->input['merchant']))
        {
            if ($this->input['merchant']->isTPVRequired())
            {
                return $this->config['test_hash_secret_tpv'];
            }
        }

        return $this->config['test_hash_secret'];
    }

    protected function buildQueryString($data)
    {
        $str = '';

        $amp = '';

        foreach ($data as $key => $value)
        {
            $str .= $amp .$key.'='.$value;

            if ($amp === '')
                $amp = '&';
        }

        return $str;
    }

    protected function stripEmailSpecialChars($email)
    {
        return preg_replace("/[^a-zA-Z0-9]+/", "", $email);
    }

    protected function getRecurringData()
    {
        $recurringData = [
            Token\Entity::RECURRING_STATUS         => Token\RecurringStatus::INITIATED,
        ];

        return $recurringData;
    }
}
