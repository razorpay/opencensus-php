<?php

namespace Gateway\NetBanking\Hdfc;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\BaseGateway;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends BaseGateway
{
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

    /**
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $requestData = $this->getPaymentRequestData($input);

        $queryStr = $this->buildQueryString($requestData);

        $url = $this->getDomain() . Url::PAYMENT_URL;
        $url = $url . '?' . $queryStr;

        $data = array('redirectUrl' => $url);

        return $data;
    }

    public function capture(array $input = array())
    {
        return parent::capture($input);
    }

    /**
     * We recieve callback from atom after bank net-banking transaction
     * is complete
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

        $data = array(
            'ClientCode'        => 'ab', //$input['terminal'],
            'MerchantCode'      => 'ab', //$input['terminal'],
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
            $data['ClientCode'] = random_alpha_string(10);
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

        foreach ($data as $key => $value)
        {
            $str .= '&'.$key.'='.$value;
        }

        return $str;
    }
}
