<?php

namespace Gateway\UPI\ICICI;

use Gateway\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_icici';

    public function authorize(array $input)
    {
        $this->makeCollectRequest($input);
    }

    protected function makeCollectRequest(array $input)
    {
        $data = $this->generateCollectRequestData($input);

        $this->makeRequest($data);
    }

    protected function makeRequest(array $data)
    {
        $request = new Request();

        // TODO: Improve on the request<>gateway interface
        $response = $this->sendGatewayRequest($request->collectPay($data));

        assert($response->status_code === 200);

        $response = json_decode($response->body, true);

        assert($response !== null);

        assert($response['response'] === '9');

        $bankRRN = $response['BankRRN'];

        // Write the bankRRN into the database
    }

    protected function formatAmount($amount)
    {
        return number_format($amount/100, 2);
    }

    protected function getMerchantId()
    {
        // TODO: Decide between LIVE/TEST
        return $this->config['test_merchant_id'];
    }

    protected function generateCollectRequestData(array $input)
    {
        $payment = $input['payment'];

        return [
            // Amount and note are lowercase
            // despite being uppercase in docs
            "amount"        =>  $this->formatAmount($payment['amount']),
            "billNumber"    =>  "sdf234234",
            "collectByDate" =>  "15/12/2016 11:01 AM",
            "merchantId"    =>  $this->getMerchantId(),
            "merchantName"  =>  $input['merchant']['billing_label'],
            "merchantTranId"=>  $payment['id'],
            "note"          =>  "collect-pay-request",
            // TODO: talk to icici and ask what all is allowed here
            "payerVa"       =>  "testing1@imobile",
            "subMerchantId" =>  "1234",//$input['merchant']['id'],
            "subMerchantName"=> $input['merchant']['name'],
            "terminalId"    =>  "1234",
        ];
    }

    /**
     * Generates request object for the status call
     * @return array
     */
    protected function statusData()
    {
        return [
            "merchantId"        =>  "merchantId",
            "subMerchantId"     =>  "12234",
            "terminalId"        =>  "2342342",
            "merchantTranId"    =>  "612413726581"
        ];
    }
}
