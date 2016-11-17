<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_hdfc';

    protected $bank = 'hdfc';

    protected $sortRequestContent = false;

    protected $fields = array(
        'ClientCode',
        'MerchantCode',
        'TxnCurrency',
        'TxnAmount',
        'TxnScAmount',
        'MerchantRefNo',
        'SuccessStatifFlag',
        'FailureStaticFlag',
        'Date',
    );

    protected $map = array(
        'ClientCode'    => 'client_code',
        'MerchantCode'  => 'merchant_code',
        'TxnAmount'     => 'amount',
        'Message'       => 'error_message',
        'BankRefNo'     => 'bank_payment_id',
        'fldSessionNbr' => 'reference1',
        'Date'          => 'date',
    );

    /**
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $payment = $this->createGatewayPaymentEntity($content);

        $request = array(
            'url' => $this->getUrl('pay'),
            'method' => 'post',
            'content' => $content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function capture(array $input = array())
    {
        return parent::capture($input);
    }

    /**
     * We recieve callback from atom after bank net-banking
     * transaction is complete
     *
     * @param  array    $input
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $this->validateCallbackChecksum($input);
        unset($input['gateway']['CheckSum']);

        // Unset date because format of date returned is different than what we sent
        unset($input['gateway']['Date']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $bankRefNo = $input['gateway']['BankRefNo'];
        $message = $input['gateway']['Message'];

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

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function validateCallbackChecksum($input)
    {
        $expectedChecksum = $this->getCallbackChecksum($input['gateway']);

        $checksum = $input['gateway']['CheckSum'];

        if ($checksum !== $expectedChecksum)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getPaymentRequestData($input)
    {
        $date = Carbon::now('Asia/Kolkata')->format('d/m/Y H:m:s');

        $clientCode = $this->stripEmailSpecialChars($input['payment']['email']);

        $data = array(
            'ClientCode'        => $clientCode,
            'MerchantCode'      => $input['terminal']['gateway_merchant_id'],
            'TxnCurrency'       => 'INR',
            'TxnAmount'         => $input['payment']['amount'] / 100,
            'TxnScAmount'       => '0',
            'MerchantRefNo'     => $input['payment']['id'],
            'SuccessStaticFlag' => 'N',
            'FailureStaticFlag' => 'N',
            'Date'              => $date,
            'DynamicUrl'        => $input['callbackUrl'],
        );

        if ($this->mode === Mode::TEST)
        {
            $data['MerchantCode'] = 'RAZORPAY';
//            $data['ClientCode'] = random_alpha_string(10);
        }

        $data['CheckSum'] = $this->generateHash($data);

        return $data;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $date = Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')
                      ->format('d/m/Y H:m:s');

        if (empty($payment['date']) === false)
        {
            // First verify all hdfc netbanking transactions here and
            // then remove this in future.
            // $date = $payment['date'];
        }

        $clientCode = $payment['client_code'];

        if ($clientCode === 'client_code')
        {
            $clientCode = $input['payment']['email'];
        }

        $content = array(
            'MerchantCode'          => $input['terminal']['gateway_merchant_id'],
            'Date'                  => $date,
            'MerchantRefNo'         => $payment['payment_id'],
            'TransactionId'         => 'XTXTV01',
            'FlgVerify'             => 'Y',
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

        $days = (time() - $input['payment']['created_at']) / (24*60*60);

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

    protected function getTestSecret()
    {
        assert ($this->mode === Mode::TEST);

        return '123456';
    }

    protected function getLiveSecret()
    {
        assert ($this->mode === Mode::LIVE);

        return $this->config['live_hash_secret'];
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
}
