<?php

namespace Gateway\Netbanking\Hdfc;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Netbanking\Base;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'netbanking_hdfc';

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
        'BankRefNo'     => 'bank_payment_id'
    );

    /**
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $queryStr = $this->buildQueryString($content);

        $request = array(
            'url' => $this->getUrl('pay'),// . '?' . $queryStr,
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
        $this->verifyCallbackChecksum($input);

        $bankRefNo = $input['BankRefNo'];
        $message = $input['Message'];
    }

    public function verify(array $input)
    {
        $data = ''; // Get the parameters required from db or elsewhere

        $data['TransactionId'] = 'XTXTV01';
        $data['FigVerify'] = 'Y';

        $url = $this->getDomain() . Url::VERIFY_URL;
        $request['url'] = $url . $this->buildQueryString($data);

        $request['method'] = 'GET';

        $response = $this->postRequest($request);

        $status = $response['data']['figSuccess'];
        $bankRefNo = $response['date']['BankRefNo'];

        // verify and match params
    }

    protected function verifyCallbackChecksum($input)
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

        $checksum = $input['CheckSum'];

        // s($input, $data, $str);
        $expectedChecksum = $this->getChecksumForString($str);

        if ($checksum !== $expectedChecksum)
        {
            throw new Exception\BadRequestException('Failed checksum verification');
        }
    }

    protected function getPaymentRequestData($input)
    {
        $date = Carbon::now('Asia/Kolkata')->format('d/m/Y H:m:s');

        $clientCode = $this->stripEmailSpecialChars($input['payment']['email']);

        $data = array(
            'ClientCode'        => $input['payment']['email'],//$clientCode,
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

        $data['CheckSum'] = $this->getChecksumForData($data);

        return $data;
    }

    protected function getChecksumForData($data)
    {
        $str = '';

        foreach ($data as $key => $value)
        {
            $str = $str.= $value;
        }

        return $this->getChecksumForString($str);
    }

    protected function getChecksumForString($str = '')
    {
        return (string) crc32($str . '123456');
    }

    protected function getDomain()
    {
        return ($this->mode === Mode::LIVE) ? Url::LIVE_DOMAIN : Url::TEST_DOMAIN;
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

    protected function getUrl($type)
    {
        $url = $this->getUrlDomain();

        $type = strtoupper($type);
        $url .= $this->getRelativeUrl($type);

        return $url;
    }

    protected function getUrlDomain()
    {
        return ($this->mode === MODE::LIVE) ? Url::LIVE_DOMAIN : Url::TEST_DOMAIN;
    }

    protected function getRelativeUrl($type)
    {
        return constant(__NAMESPACE__.'\Url::'.$type);
    }
}
