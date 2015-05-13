<?php

namespace Gateway\Kotak\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Kotak;
use Gateway\Base;
use Models\Card;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today('Asia/Kolkata')->format('dmY');

        $card = $this->getMaskedCardNo($input['CardNumber']);

        $content = array(
            'TxnRefNo'      => $input['TxnRefNo'],
            'MerchantId'    => $input['MerchantId'],
            'Amount'        => $input['Amount'],
            'TerminalId'    => $input['TerminalId'],
            'ResponseCode'  => '00',
            'Message'       => 'Successful transaction',
            'BatchNo'       => $date,
            'RetRefNo'      => random_integer(12),
            'AuthCode'      => random_integer(6),
            'CardType'      => 'RuPay',
            'MaskedCardNo'  => $card,
        );

        $responseCode = $this->getAuthResponseCode($content, $input);

        $content['SecureHash'] = $this->generateHash($content);

        $url = $input['ReturnURL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    protected function getMaskedCardNo($card)
    {
        $masked = substr($card, 0, 6);
        $last4 = substr($card, -4);
        $xCount = strlen($card) - 10;
        $masked .= str_repeat('x', $xCount) . $last4;

        return $masked;
    }

    protected function getAuthResponseCode(array & $content, $input)
    {
        if ($input['CardNumber'] === '6070020000000018')
        {
            $respCode = 'VER';
            $message = 'Validation Error Occurs if field data is incorrect';
        }
        else if (($input['CardNumber'] === '6075000000000015') and
                 ($input['ExpiryDate'] === '1705'))
        {
            $respCode = 'VER';
            $message = 'Transaction declined';
        }
        else
        {
            $respCode = '00';
            $message = 'Transaction successful';
        }

        $content['ResponseCode'] = $respCode;
        $content['Message'] = $message;
    }
}