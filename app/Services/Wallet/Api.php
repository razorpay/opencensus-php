<?php

namespace RZP\Services\Wallet;

use RZP\Http\Request\Requests;

class Api extends Base
{
    const USER = 'api';

    const REFUND_URL   = '/v1/refund';
    const PAYMENT_URL  = '/v1/payment';
    const RECHARGE_URL = '/v1/recharge';
    const TRANSFER_URL = '/v1/transfer';
    const CAPTURE_URL  = '/v1/payment/:PAYMENT_ID/capture';

    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * This api will make a call to wallet service to recharge the customer wallet
     *
     * @param array $data
     * @return array
     * @throws \Throwable
     */
    public function recharge(array $data) : array
    {
        (new Validator)->validateInput('recharge', $data);

        $body = [
            'merchant_id'   => $data['merchant_id'],
            'user_id'       => $data['user_id'],
            'reference_id'  => $data['transfer_id'],
            'amount'        => $data['amount'],
            'notes'         => isset($data['notes']) ? $data['notes'] : null,
        ];

        return $this->makeRequest(Requests::POST, self::RECHARGE_URL, $body, self::USER);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Throwable
     */
    public function payment(array $data) : array
    {
        (new Validator)->validateInput('payment', $data);

        $body = [
            'merchant_id'   => $data['merchant_id'],
            'user_id'       => isset($data['user_id']) ? $data['user_id'] : null,
            'reference_id'  => $data['payment_id'],
            'amount'        => $data['amount'],
            'customer_consent' => $data['customer_consent'],
            'contact'       => isset($data['contact']) ? $data['contact'] : null,
            'notes'         => isset($data['notes']) ? $data['notes'] : null,
        ];

        return $this->makeRequest(Requests::POST, self::PAYMENT_URL, $body, self::USER);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Throwable
     */
    public function refund(array $data) : array
    {
        (new Validator)->validateInput('refund', $data);

        $body = [
            'merchant_id'             => $data['merchant_id'],
            'user_id'                 => $data['user_id'],
            'reference_id'            => $data['refund_id'],
            'payment_reference_id'    => $data['payment_id'],
            'amount'                  => $data['amount'],
            'notes'                   => isset($data['notes']) ? $data['notes'] : null,
        ];

        return $this->makeRequest(Requests::POST, self::REFUND_URL, $body, self::USER);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Throwable
     */
    public function transfer(array $data) : array
    {
        (new Validator)->validateInput('transfer', $data);

        $body = [
            'merchant_id'   => $data['merchant_id'],
            'user_id'       => $data['user_id'],
            'reference_id'  => $data['reference_id'],
            'utr'           => $data['utr'],
            'amount'        => $data['amount'],
            'notes'         => isset($data['notes']) ? $data['notes'] : null,
        ];

        return $this->makeRequest(Requests::POST, self::TRANSFER_URL, $body, self::USER);
    }

    public function capture(array $data) : array
    {
        (new Validator)->validateInput('capture', $data);

        $body = [
            'amount'           => $data['amount'],
        ];

        $captureUrl = str_replace(":PAYMENT_ID",$data['payment_id'],self::CAPTURE_URL);

        return $this->makeRequest(Requests::POST, $captureUrl, $body, self::USER);
    }
}
