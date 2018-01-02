<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
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
    ];

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

        $this->traceGatewayPaymentRequest($request, $input);

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

        $this->assertPaymentId($input['payment']['id'], $input['gateway']['MerchRefNo']);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
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
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
                    '',
                    $message);
        }

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $recurringData = $this->getRecurringData();

            $acquirerData = array_merge($acquirerData, $recurringData);
        }

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function reconcileRegisterEmandate(array $input)
    {
        parent::reconcileRegisterEmandate($input);

        $response = (new EMandateRegistrationReconFile)->process($input);

        return $response;
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

        $data = [
            'ClientCode'        => $clientCode,
            'MerchantCode'      => $this->getMerchantId(),
            'TxnCurrency'       => 'INR',
            'TxnAmount'         => $input['payment']['amount'] / 100,
            'TxnScAmount'       => '0',
            'MerchantRefNo'     => $input['payment']['id'],
            'SuccessStaticFlag' => 'N',
            'FailureStaticFlag' => 'N',
            'Date'              => $date,
        ];

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

            $emData = Fields::getEMandateRegistrationData($input['token']);

            $startDate = Carbon::createFromTimestamp($emData[Fields::START_TIMESTAMP], Timezone::IST)
                               ->format('dmY');

            $endDate = Carbon::createFromTimestamp($emData[Fields::END_TIMESTAMP], Timezone::IST)
                             ->format('dmY');

            $data[Fields::CLIENT_ACCOUNT_NUMBER] = $emData[RHeadings::CUSTOMER_ACCOUNT_NUMBER];
            $data[Fields::REF1]                  = $emData[RHeadings::MERCHANT_UNIQUE_REFERENCE_NO];
            $data[Fields::REF2]                  = $emData[RHeadings::CUSTOMER_NAME];
            $data[Fields::REF3]                  = $emData[RHeadings::CUSTOMER_ACCOUNT_NUMBER];
            $data[Fields::REF4]                  = $input['payment']['amount'] / 100;
            $data[Fields::REF5]                  = $emData[RHeadings::FREQUENCY];
            $data[Fields::REF6]                  = $emData[RHeadings::MANDATE_SERIAL_NUMBER];
            $data[Fields::REF7]                  = $emData[RHeadings::MANDATE_ID];
            $data[Fields::REF8]                  = $emData[RHeadings::MERCHANT_REQUEST_NO];
            $data[Fields::REF9]                  = $emData[RHeadings::AMOUNT_TYPE];
            $data[Fields::REF10]                 = $emData[RHeadings::CLIENT_NAME];
            $data[Fields::DATE1]                 = $startDate;
            $data[Fields::DATE2]                 = $endDate;
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

        $content = array(
            'MerchantCode'          => $this->getMerchantId(),
            'Date'                  => $date,
            'MerchantRefNo'         => $payment['payment_id'],
            'TransactionId'         => 'XTXTV01',
            'FlgVerify'             => $flgVerify,
            'ClientCode'            => $clientCode,
            'SuccessStaticFlag'     => 'N',
            'FailureStaticFlag'     => 'N',
            'TxnAmount'             => $input['payment']['amount'] / 100,
        );

        $url = $this->getUrl();

        $request['url'] = $url . '?' . $this->buildQueryString($content);
        $request['method'] = 'get';
        $request['content'] = [];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
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
        $payment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $days = (time() - $input['payment']['created_at']) / (24 * 60 * 60);

        $status = VerifyResult::STATUS_MATCH;

        //
        // In HDFC netbanking, the bank only stores the payment data for
        // 45 days! So for verification requests after 45 days, we simply
        // treat it as successful and return.
        //
        if ($days > 45)
        {
            $verify->apiSuccess = true;
            $verify->gatewaySuccess = true;
            $verify->match = true;

            $this->trace->info(
                TraceCode::GATEWAY_PAYMENT_VERIFY,
                [
                    'message' => 'In HDFC netbanking, the bank only stores the payment data for 45 days!' .
                        ' Since it has been more than 45 days, we simply treat it as successful and return.',
                    'payment_id' => $input['payment']['id']
                ]);

            return $status;
        }

        $verify->apiSuccess = (($input['payment']['status'] !== Payment\Status::CREATED) and
                               ($input['payment']['status'] !== Payment\Status::FAILED));

        $verify->gatewaySuccess = ($content['flgSuccess'] === 'S');

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        if ($payment['received'] === false)
        {
            $attrs = $this->getMappedAttributes($content);
            $payment->fill($attrs);
            $payment->saveOrFail();
        }

        return $status;
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

        $clientCode = $this->stripEmailSpecialChars($email);

        return $clientCode;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        $body = $response->body;

        $msg = 'Unable to reach destination.';

        if (strpos($body, $msg) !== false)
        {
            throw new Exception\GatewayTimeoutException(
                'Hdfc netbanking gateway could not be reached');
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
        assert ($this->mode === Mode::LIVE);

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
        assert ($this->mode === Mode::TEST);

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
