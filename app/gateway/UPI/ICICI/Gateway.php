<?php

namespace Gateway\UPI\ICICI;

use Gateway\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = 'upi_icici';

    protected function genKey()
    {
        return random_alphanum_string(32);
    }

    /** Generates request object for the status call */
    protected function statusData()
    {
        return [
            "merchantId"        =>  "merchantId",
            "subMerchantId"     =>  "12234",
            "terminalId"        =>  "2342342",
            "merchantTranId"    =>  "612413726581"
        ];
    }

    public function authorize(array $input)
    {
        $this->makeCollectRequest($input);
    }

    protected function makeCollectRequest(array $input)
    {
        $data = $this->generateCollectRequestData($input);
    }

    protected function makeRequest(array $data)
    {
        $body = json_encode($data, JSON_PRETTY_PRINT);
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
            "billNumber"    =>  null,
            "collectByDate" =>  null,
            "merchantId"    =>  $this->getMerchantId(),
            "merchantName"  =>  $input['merchant']['billing_label'],
            "merchantTranId"=>  $payment['id'],
            "note"          =>  null,
            // TODO: talk to icici and ask what all is allowed here
            "payerVa"       =>  "testing1@imobile",
            "subMerchantId" =>  $input['merchant']['id'],
            "subMerchantName"=> $input['merchant']['name'],
            "terminalId"    =>  null,
        ];
    }
}
