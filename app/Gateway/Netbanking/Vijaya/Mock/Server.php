<?php

namespace RZP\Gateway\Netbanking\Vijaya\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Netbanking;
use RZP\Gateway\Netbanking\Vijaya\RequestFields;
use RZP\Gateway\Netbanking\Vijaya\ResponseFields;
use RZP\Gateway\Netbanking\Vijaya\Constants;
use RZP\Gateway\Netbanking\Vijaya\Status;

class Server extends Base\Mock\Server
{
    use Base\Mock\GatewayTrait;

    const BANK_REF_NUMBER = '12345678';

    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $authResponseContent = $this->getAuthResponseContent($input);

        $redirectUrl = $input[RequestFields::RETURN_URL];

        $request = [
            'url'     => $redirectUrl,
            'content' => $authResponseContent,
            'method'  => 'post',
        ];

        return $this->makePostResponse($request);
    }

    public function verify($input)
    {
        $this->validateActionInput($input, 'verify');

        $content = '<HTML><head><title> Shopping Mall Message Page </title></head><body><LINK rel="stylesheet" href="" type="text/css"><BODY bgcolor="#ffffff" link="#ff3300" vlink="#bbbbbb"><table border=0 width=100% CELLPADDING="5" cellspacing=0><tr><td><H4>Your Payment is Successful</H4>&nbsp;</TD></tr></table><center><!--p--><a href="">Return To Shopping Site<a/><!--/p--></center></body></HTML>';

        $this->content($content, 'verify');

        return $this->makeResponse($content);
    }

    protected function getAuthResponseContent($content)
    {
        $data = [
            ResponseFields::STATUS                => Status::SUCCESS,
            ResponseFields::BANK_REFERENCE_NUMBER => self::BANK_REF_NUMBER,
            ResponseFields::MODE                  => Constants::PAYMENT_MODE,
            ResponseFields::CURRENCY              => Constants::INDIAN_CURRENCY,
            ResponseFields::AMOUNT                => $content[RequestFields::AMOUNT],
            ResponseFields::MERCHANT_CONSTANT     => $content[RequestFields::MERCHANT_CONSTANT],
            ResponseFields::PAYMENT_ID            => $content[RequestFields::PAYMENT_ID],
            ResponseFields::ITEM_CODE             => $content[RequestFields::ITEM_CODE]
        ];

        $this->content($data, 'authorize');

        return $data;
    }
}
