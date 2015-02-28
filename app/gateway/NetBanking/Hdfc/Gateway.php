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
        $url = 'domain'.$queryStr;

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
        $checksum = $input['gateway']['checksum'];
        unset($input['gateway']['checksum']);

        $expectedChecksum = $this->getChecksum($input['gateway']);

        if ($checksum !== $expectedChecksum)
        {
            // fail payment
            ;
        }

        $bankRefNo = $input['gateway']['BankRefNo'];
        $message = $input['gateway']['Message'];
    }

    public function verify(array $input)
    {
        $data = ''; // Get the parameters required from db or elsewhere

        $data['TransactionId'] = 'XTXTV01';
        $data['FigVerify'] = 'Y';

        $request['url'] = 'url domain' . $this->buildQueryString($data);

        $request['method'] = 'GET';

        $response = $this->postRequest($request);

        $status = $response['data']['figSuccess'];
        $bankRefNo = $response['date']['BankRefNo'];

        // verify and match params
    }

    protected function getPaymentRequestData($input)
    {
        $date = Carbon::now('Asia/Kolkata')->format('D/M/y');

        $data = array(
            'ClientCode'        => $input['terminal'],
            'MerchantCode'      => $input['terminal'],
            'TxnCurrency'       => 'INR',
            'TxnAmount'         => $input['payment']['amount'],
            'TxnScAmount'       => '0',
            'MerchantRefNo'     => $input['payment']['id'],
            'SuccessStaticFlag' => 'N',
            'FailureStaticFlag' => 'N',
            'Date'              => $date,
            'DynamicUrl'        => $input['callbackUrl'],
        );

        $data['CheckSum'] = $this->getChecksum($data);

        return $data;
    }

    protected function getChecksum($data)
    {
        $str = '';

        foreach ($data as $key => $value)
        {
            $str = $str.= $value;
        }

        $checksum = crc32($str . 'checksum_key');

        return $checksum;
    }
}
