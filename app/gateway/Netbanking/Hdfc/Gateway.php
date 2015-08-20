<?php

namespace Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base\Action;
use Gateway\Base\Verify;
use Gateway\Base\VerifyResult;
use Gateway\Netbanking\Base;
use Symfony\Component\DomCrawler\Crawler;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
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

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $bankRefNo = $input['gateway']['BankRefNo'];
        $message = $input['gateway']['Message'];

        $attrs = $this->getMappedAttributes($input['gateway']);
        $attrs['received'] = true;

        $payment->fill($attrs);
        $payment->saveOrFail();

        if (($bankRefNo === '') or
            ($message !== ''))
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
                    '',
                    $message);
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
                    $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
    }

    protected function validateCallbackChecksum($input)
    {
        $expectedChecksum = $this->getCallbackChecksum($input['gateway']);

        $checksum = $input['gateway']['CheckSum'];

        if ($checksum !== $expectedChecksum)
        {
            throw new Exception\BadRequestValidationFailureException('Failed checksum verification');
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            [$response->body]);

        $crawler = new Crawler($response->body, $request['url']);

        $form = $crawler->filter('form')->form();

        $values = $form->getValues();

        $url = $values['REDIRECTURL'];

        $content = [];
        $parts = parse_url($url);
        parse_str($parts['query'], $content);

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

        if ($days > 45)
        {
            $verify->match = true;
            $verify->status = VerifyResult::STATUS_MATCH;

            return;
        }

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = (($input['payment']['status'] === 'authorized') or
                               ($input['payment']['status'] === 'captured'));

        $verify->gatewaySuccess = ($content['flgSuccess'] === 'S');

        if (($verify->apiSuccess === false) and
            ($verify->gatewaySuccess === true))
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $attrs = $this->getMappedAttributes($content);
        $payment->fill($attrs);
        $payment->saveOrFail();

        return $status;
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
