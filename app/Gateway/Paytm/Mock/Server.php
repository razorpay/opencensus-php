<?php

namespace RZP\Gateway\Paytm\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Paytm;
use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $method = $this->getAuthMethod($input);

        $content = array(
            'MID'           => $input['MID'],
            'ORDERID'       => $input['ORDER_ID'],
            'TXNAMOUNT'     => $input['TXN_AMOUNT'],
            'CURRENCY'      => 'INR',
            'TXNID'         => random_integer(6),
            'BANKTXNID'     => $this->getBankTxnId(),
            'STATUS'        => Paytm\Status::SUCCESS,
            'RESPCODE'      => '01',
            'TXNDATE'       => $this->getTxnDate(),
            'RESPMSG'       => 'Txn Successful.',
            'GATEWAYNAME'   => 'INDB',
            'BANKNAME'      => 'Axis Bank',
        );

        if ($method === 'wallet')
        {
            $content['GATEWAYNAME'] = 'WALLET';
            $content['BANKNAME'] = '';
            $content['PAYMENTMODE'] = 'PPI';
        }

        $this->getStatusAndResponseDetails($content, $input);

        if (isset($input['PAYMENT_TYPE_ID']))
        {
            $content['PAYMENTMODE'] = $input['PAYMENT_TYPE_ID'];
        }

        if ($content['STATUS'] !== Paytm\Status::SUCCESS)
        {
            $content['BANKTXNID'] = '';
        }

        $code = $content['RESPCODE'];
        $content['RESPMSG'] = Paytm\ResponseCode::getResponseMessage($code);

        $content = $this->content($content);

        $secret = \Config::get('gateway.paytm')['test_hash_secret'];

        $content['CHECKSUMHASH'] = paytm\Checksum::getChecksumFromArray($content, $secret);

        $url = $input['CALLBACK_URL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        $input = json_decode($input['JsonData'], true);

        parent::verify($input);

        $id = $input['ORDERID'];

        $payment = (new Paytm\Repository)->findByPaymentIdAndAction($id, Action::AUTHORIZE);

        $fields = array(
            'txnid',
            'banktxnid',
            'orderid',
            'txnamount',
            'status',
            'txntype',
            'gatewayname',
            'respcode',
            'respmsg',
            'bankname',
            'mid',
            'paymentmode',
            'refundamt',
            'txndate',
        );

        $content = [];

        foreach ($fields as $field)
        {
            $content[strtoupper($field)] = $payment[$field];
        }

        $content = $this->content($content);

        return $this->makeResponse(json_encode($content));
    }

    public function refund($input)
    {
        $input = json_decode($input, true);

        parent::refund($input);

        $this->validateActionInput($input['body'], 'refund_body');
        $this->validateActionInput($input['head'], 'refund_head');

        $content = [
            'body' => [
                'txnTimestamp' => $this->getTxnDate(),
                'orderId'      => $input['body']['orderId'],
                'mid'          => $input['body']['mid'],
                'refId'        => $input['body']['refId'],
                'resultInfo'   => [
                    'resultStatus'=> 'PENDING',
                    'resultCode'  => '601',
                    'resultMsg'   => 'Refund request was raised for this transaction. But it is pending state'
                ],
                'refundId'     => $this->getBankTxnId(),
                'txnId'        => $input['body']['txnId'],
                'refundAmount' => $input['body']['refundAmount']
            ]
        ];

        $secret = \Config::get('gateway.paytm')['test_hash_secret'];

        $content['head']['signature'] = Paytm\Checksum::getChecksumFromString(json_encode($content['body']), $secret);

        return $this->makeResponse(json_encode($content));
    }

    public function verifyRefund($input)
    {
        $input = json_decode($input, true);

        parent::verifyRefund($input);

        $this->validateActionInput($input['body'], 'verify_refund_body');
        $this->validateActionInput($input['head'], 'verify_refund_head');

        $content = [
            'body' => [
                'orderId'                        => $input['body']['orderId'],
                'userCreditInitiateStatus'       => 'SUCCESS',
                'mid'                            => $input['body']['mid'],
                'merchantRefundRequestTimestamp' => $this->getTxnDate(),
                'source'                         => 'MERCHANT',
                'resultInfo'                     => [
                    'resultStatus' => 'TXN_SUCCESS',
                    'resultCode'   => '10',
                    'resultMsg'    => 'Refund successful'
                ],
                'txnTimestamp'                   => $this->getTxnDate(),
                'acceptRefundStatus'             => 'SUCCESS',
                'totalRefundAmount'              => '500.00',
                'refId'                          => $input['body']['refId'],
                'txnAmount'                      => '500.00',
                'refundId'                       => $this->getBankTxnId(),
                'txnId'                          => $input['body']['orderId']
            ]
        ];

        $content = $this->content($content, 'verify_refund');

        $secret = \Config::get('gateway.paytm')['test_hash_secret'];

        $content['head']['signature'] = Paytm\Checksum::getChecksumFromString(json_encode($content['body']), $secret);

        return $this->makeResponse(json_encode($content));
    }

    protected function makeResponse($json)
    {
        $response = \Response::make($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getStatusAndResponseDetails(array & $content, $input)
    {
        if (isset($input['PAYMENT_DETAILS']))
        {
            // Card flow
            $this->getStatusAndResponseDetailsForCard($content, $input);
        }
        else
        {
            ;
        }

        if ($content['RESPCODE'] !== '01')
        {
            $content['STATUS'] = Paytm\Status::FAILURE;
        }
    }

    protected function getStatusAndResponseDetailsForCard(array & $content, $input)
    {
        $card = $this->getCardDetails($input);

        if ($card['number'] === '4012001036275556')
        {
            $content['RESPCODE'] = 229;
        }
    }

    protected function getCardDetails($input)
    {
        $secret = \Config::get('gateway.paytm')['test_hash_secret'];

        $cardData = Paytm\Checksum::decrypt_e(
                            $input['PAYMENT_DETAILS'], $secret);

        $details = explode('|', $cardData);
        $card['number'] = $details[0];
        $card['cvv'] = $details[1];
        $card['expiry_date'] = $details[2];

        return $card;
    }

    protected function getAuthMethod($input)
    {
        $method = null;

        if (isset($input['PAYMENT_TYPE_ID']))
        {
            if ($input['PAYMENT_TYPE_ID'] === 'NB')
                $method = 'netbanking';
            else
                $method = 'card';
        }
        else
            $method = 'wallet';

        return $method;
    }

    protected function getBankTxnId()
    {
        // Format YYYYMMDD
        $bankTxnId = Carbon::today(Timezone::IST)->format('YmdHis');
        $bankTxnId .=  random_integer(1);

        return $bankTxnId;
    }

    protected function getTxnDate()
    {
        // Format - YYYY-MM-DD HH:MM:SS.U
        return Carbon::now(Timezone::IST)->format('Y-m-d H-i-s.0');
    }

    /**
     * For Paytm UPI get callback is being used to return callback response
     * @param array $upiEntity
     * @param array $payment
     * @return array
     */
    public function getCallback(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;

        $content = $this->getCallbackData($upiEntity, $payment);

        $this->content($content, 'callback');

        return $this->getCallbackRequest($content);
    }

    public function getCallbackRequest($data)
    {
        $url = '/callback/paytm';
        $method = 'post';

        $server = [
            'CONTENT_TYPE'                      => 'application/json',
        ];

        $raw = json_encode($data);

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];
    }

    protected function getCallbackData(array $upiEntity, array $payment)
    {
        $status     = 'TXN_SUCCESS';
        $amount     = $payment['amount'];
        $responseCode = '01';
        $responseMassage = 'Txn Success';

        switch ($payment['description'])
        {
            case 'callback_failed_v2':
                $responseCode = '0001';
                $status     = 'TXN_FAILURE';
                $responseMassage = 'FAILED';
                break;

            case 'callback_amount_mismatch_v2':
                $amount = $payment['amount'] + 100;
                break;

        }

        $content = [
            "CURRENCY"      =>  "INR",
            "GATEWAYNAME"   => "PPBLC",
            "RESPMSG"       => $responseMassage,
            "BANKNAME"      =>  "",
            "PAYMENTMODE"   => "UPI",
            "CUSTID"        => "yadav.gaurav@gamil.com",
            "MID"           => "Brain-warmer890",
            "MERC_UNQ_REF"  => "",
            "RESPCODE"      => $responseCode,
            "TXNID"         => "20210324111212800110168860592522268",
            "TXNAMOUNT"     => $amount,
            "ORDERID"       => $payment['id'],
            "STATUS"        => $status,
            "BANKTXNID"     => "108369275293",
            "TXNDATETIME"   => "2021-03-24 23:33:35.0",
            "TXNDATE"       => "2021-03-24",
            "CHECKSUMHASH"  => "S3INfP0o5QGErO+/87IGZIfMZMyB5tDfQcN32xxaTxLKgnoB0rjIenH0u/gL4s3aevIyc5PjbDpYVAzQwuZelJDb9xikJme0hJVVO8w8dyU="
        ];

        return $content;
    }

}
