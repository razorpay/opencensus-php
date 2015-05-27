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

        $this->verifyCallbackChecksum($input);

        $bankRefNo = $input['gateway']['BankRefNo'];
        $message = $input['gateway']['Message'];

        $content = $input['gateway'];

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

        $data['CheckSum'] = $this->generateHash($data);

        return $data;
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
