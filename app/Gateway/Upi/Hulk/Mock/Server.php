<?php

namespace RZP\Gateway\Upi\Hulk\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Hulk;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Hulk\Fields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Models\Payment\Entity as Payment;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->request($input, 'authorize');

        if ($input[Fields::TYPE] === Hulk\Type::EXPECTED_PUSH)
        {
            $this->validateActionInput($input, 'authorize_intent');

            $override = [
                Fields::SENDER_ID        => null,
                Fields::SENDER_TYPE      => null,
                Fields::TYPE             => 'push',
                Fields::STATUS           => 'created',
            ];
        }
        else
        {
            $this->validateAuthorizeInput($input);

            $override = [
                Fields::SENDER_ID        => '9X0HrhNT68ZWeX',
                Fields::SENDER_TYPE      => 'vpa',
            ];
        }

        $content = array_merge(
            [
                Fields::ID               => 'p2p_A11zpSL1413XHi',
                Fields::TXN_ID           => 'HDF2C8B11D1FBDB4FC78F4E37A19AB6413D',
                Fields::RECEIVER_ID      => 'A11xBDINnz4so1',
                Fields::RECEIVER_TYPE    => 'vpa',
                Fields::STATUS           => 'initiated',
                Fields::AMOUNT           => $input['amount'],
                Fields::DESCRIPTION      => $input['description'],
                Fields::TYPE             => $input['type'],
                Fields::NOTES            => $input['notes'],
                Fields::CURRENCY         => $input['currency'],
                Fields::TRANSACTION_TYPE => 'credit',
                Fields::RRN              => '0810010123456',
            ],
            $override);

        $this->content($content, 'authorize');

        return $this->makeJsonResponse($content);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $content = $this->getP2pEntity();

        $this->content($content, 'verify');

        return $this->makeJsonResponse($content);
    }

    public function getAsyncCallbackRequest(Upi $upi, Payment $payment)
    {
        $override = [
            Fields::MERCHANT_REFERENCE_ID   => $payment->getId(),
            Fields::ID                      => $upi->getGatewayPaymentId(),
        ];

        $data = $this->getP2pEntity($override);

        $content = [
            'type'      => 'p2p_completed',
            'data'      => $data,
            'timestamp' => Carbon::now()->getTimestamp(),
        ];

        $request = [
            'url'       => '/callback/upi_hulk',
            'method'    => 'post',
            'content'   => $content,
            'server'   => [
                'HTTP_X-Hulk-Signature' => $this->getHmac(json_encode($content)),
            ]
        ];

        return $request;
    }

    protected function getP2pEntity(array $override = [])
    {
        $p2p = [
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
            Fields::TRANSACTION_TYPE => 'credit',

            Fields::SENDER           => [
                'id'                 => 'vpa_9X0HrhNT68ZWeX',
                'entity'             => 'vpa',
                'address'            => 'vishnu@icici',
            ],

            Fields::RECEIVER         => [
                'id'                 => 'vpa_A11xBDINnz4so1',
                'entity'             => 'vpa',
                'address'            => 'testmerchant@razor',
            ],
        ];

        return array_replace_recursive($p2p, $override);
    }

    protected function makeJsonResponse(array $content)
    {
        $json = json_encode($content);

        $response = $this->makeResponse($json);

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    protected function getHmac(string $content)
    {
        return hash_hmac('sha256', $content, 'hulk_api_password');
    }
}
