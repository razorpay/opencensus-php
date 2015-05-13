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
        $date = Carbon::today('Asia/Kolkata')->format('Y-m-d H-i-s.0');

        $content = array(
            'MID'           => $input['MID'],
            'ORDERID'       => $input['ORDER_ID'],
            'TXNAMOUNT'     => $input['TXN_AMOUNT'],
            'CURRENCY'      => 'INR',
            'TXNID'         => '153355',
            'BANKTXNID'     => '201505138150848',
            'STATUS'        => 'TXN_SUCCESS',
            'RESPCODE'      => '01',
            'RESPMSG'       => 'Txn Successful.',
            'TXNDATE'       => $date,
            'GATEWAYNAME'   => 'ICICI',
            'BANKNAME'      => 'Axis Bank',
            'PAYMENTMODE'   => $input['PAYMENT_TYPE_ID'],
        );

        $this->getStatusAndResponseDetails($content, $input);

        $content['CHECKSUMHASH'] = $this->generateHash($content);

        $url = $input['CALLBACK_URL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    protected function getStatusAndResponseDetails(array & $content, $input)
    {
        ;//$status =
    }
}
