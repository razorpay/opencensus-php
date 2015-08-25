<?php

namespace Gateway\Mobikwik\Mock;

use Carbon\Carbon;
use Gateway\Mobikwik;
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
            'statuscode'           => '0',
            'orderid'       => $input['orderid'],
            'amount'     => $input['amount'],
            'statusmessage'      => 'Transaction completed Successfully',
            'mid'         => $input['mid'],
            'refid'     => '12345'
        );


        $content['checksum'] = $this->generateHash($content);
//        sd($content);

        $url = $input['redirecturl'];
        $url .= '?' . http_build_query($content);

        return $url;
//        return ($this->makeResponse(json_encode($content)));
    }

    public function verify($input)
    {
        $id = $input['orderid'];
//        $merchantId = $input['mid'];

        $payment = (new Mobikwik\Repository)->findByPaymentIdAndAction(
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
            'txid'       => $input['txid'],
            'statuscode'     => '0',
            'status'        => 'success',
            'refid'      => '12345',
            'statusmessage'       => 'Some message'
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

//    protected function getStatusAndResponseDetails(array & $content, $input)
//    {
//        if (isset($input['PAYMENT_DETAILS']))
//        {
//            // Card flow
//            $this->getStatusAndResponseDetailsForCard($content, $input);
//        }
//        else
//        {
//            ;
//        }
//
//        if ($content['RESPCODE'] !== '01')
//        {
////            $content['STATUS'] = Mobikwik\Status::FAILURE;
//        }
//    }

//    protected function getStatusAndResponseDetailsForCard(array & $content, $input)
//    {
//        $card = $this->getCardDetails($input);
//
//        if ($card['number'] === '4012001036275556')
//        {
//            $content['RESPCODE'] = 229;
//        }
//    }
//
//    protected function getCardDetails($input)
//    {
//        $secret = \Config::get('gateway.paytm')['test_hash_secret'];
//
//        $cardData = Paytm\Checksum::decrypt_e(
//                            $input['PAYMENT_DETAILS'], $secret);
//
//        $details = explode('|', $cardData);
//        $card['number'] = $details[0];
//        $card['cvv'] = $details[1];
//        $card['expiry_date'] = $details[2];
//
//        return $card;
//    }

    protected function getAuthMethod($input)
    {
//        $method = null;
//
//        if (isset($input['PAYMENT_TYPE_ID']))
//        {
//            if ($input['PAYMENT_TYPE_ID'] === 'NB')
//                $method = 'netbanking';
//            else
//                $method = 'card';
//        }
//        else
            $method = 'wallet';

        return $method;
    }


}
