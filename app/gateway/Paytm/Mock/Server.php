<?php

namespace Gateway\Paytm\Mock;

use Carbon\Carbon;
use Gateway\Paytm;
use Gateway\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        // Format - YYYY-MM-DD HH:MM:SS.U
        $date = Carbon::now('Asia/Kolkata')->format('Y-m-d H-i-s.0');

        // Format YYYYMMDD
        $bankTxnId = Carbon::today('Asia/Kolkata')->format('YmdHis');
        $bankTxnId .=  random_integer(1);

        $content = array(
            'MID'           => $input['MID'],
            'ORDERID'       => $input['ORDER_ID'],
            'TXNAMOUNT'     => $input['TXN_AMOUNT'],
            'CURRENCY'      => 'INR',
            'TXNID'         => random_integer(6),
            'BANKTXNID'     => $bankTxnId,
            'STATUS'        => Paytm\Status::SUCCESS,
            'RESPCODE'      => '01',
            'TXNDATE'       => $date,
            'RESPMSG'       => 'Txn Successful.',
            'GATEWAYNAME'   => 'ICICI',
            'BANKNAME'      => 'Axis Bank',
            'PAYMENTMODE'   => $input['PAYMENT_TYPE_ID'],
        );

        $this->getStatusAndResponseDetails($content, $input);

        if ($content['STATUS'] !== Paytm\Status::SUCCESS)
            $content['BANKTXNID'] = '';

        $code = $content['RESPCODE'];
        $content['RESPMSG'] = Paytm\ResponseCode::getResponseMessage($code);

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $url = $input['CALLBACK_URL'];
        $url .= '?' . http_build_query($content);

        return $url;
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
}
