<?php

namespace RZP\Gateway\Upi\Hulk\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Hulk;
use RZP\Models\Payment;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Hulk\Fields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = [
            Fields::ID               => 'p2p_A11zpSL1413XHi',
            Fields::TXN_ID           => 'HDF2C8B11D1FBDB4FC78F4E37A19AB6413D',
            Fields::SENDER_ID        => '9X0HrhNT68ZWeX',
            Fields::SENDER_TYPE      => 'vpa',
            Fields::RECEIVER_ID      => 'A11xBDINnz4so1',
            Fields::RECEIVER_TYPE    => 'vpa',
            Fields::STATUS           => 'initiated',
            Fields::AMOUNT           => $input['amount'],
            Fields::DESCRIPTION      => $input['description'],
            Fields::TYPE             => $input['type'],
            Fields::NOTES            => $input['notes'],
            Fields::CURRENCY         => $input['currency'],
            Fields::TRANSACTION_TYPE => 'credit'
        ];

        $this->content($content, 'authorize');

        return $this->makeJsonResponse($content);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $content = [
            Fields::ID               => 'p2p_A11zpSL1413XHi',
            Fields::TXN_ID           => 'HDF2C8B11D1FBDB4FC78F4E37A19AB6413D',
            Fields::SENDER_ID        => '9X0HrhNT68ZWeX',
            Fields::SENDER_TYPE      => 'vpa',
            Fields::RECEIVER_ID      => 'A11xBDINnz4so1',
            Fields::RECEIVER_TYPE    => 'vpa',
            Fields::STATUS           => 'completed',
            Fields::AMOUNT           => 50000,
            Fields::DESCRIPTION      => '',
            Fields::TYPE             => 'pull',
            Fields::NOTES            => [],
            Fields::CURRENCY         => 'INR',
            Fields::TRANSACTION_TYPE => 'credit'
        ];

        $this->content($content, 'verify');

        return $this->makeJsonResponse($content);
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }
}
