<?php

namespace Gateway\Paytm\Mock;

use Carbon\Carbon;
use Gateway\Paytm;
use Gateway\Base;
use Gateway\Base\Action;

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
            'GATEWAYNAME'   => 'ICICI',
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

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $url = $input['CALLBACK_URL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function verify($input)
    {
        $input = json_decode($input['JsonData'], true);

        parent::verify($input);

        $id = $input['ORDERID'];
        $merchantId = $input['MID'];

        $payment = (new Paytm\Repository)->findByPaymentIdAndAction(
                                                    $id, Action::AUTHORIZE);

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

        return $this->makeResponse(json_encode($content));
    }

    public function refund($input)
    {
        $input = json_decode($input['JsonData'], true);

        parent::refund($input);

        $this->validateActionInput($input, 'refund');

        $content = array(
            'MID'           => $input['MID'],
            'ORDERID'       => $input['ORDERID'],
//            'TXNAMOUNT'     => $input['TXN_AMOUNT'],
            'CURRENCY'      => 'INR',
            'TXNID'         => random_integer(6),
            'BANKTXNID'     => $this->getBankTxnId(),
            'STATUS'        => Paytm\Status::SUCCESS,
            'RESPCODE'      => '01',
            'TXNDATE'       => $this->getTxnDate(),
            'RESPMSG'       => 'Txn Successful.',
            'GATEWAYNAME'   => 'ICICI',
            'BANKNAME'      => 'Axis Bank',
        );

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
        $bankTxnId = Carbon::today('Asia/Kolkata')->format('YmdHis');
        $bankTxnId .=  random_integer(1);

        return $bankTxnId;
    }

    protected function getTxnDate()
    {
        // Format - YYYY-MM-DD HH:MM:SS.U
        return Carbon::now('Asia/Kolkata')->format('Y-m-d H-i-s.0');
    }
}
