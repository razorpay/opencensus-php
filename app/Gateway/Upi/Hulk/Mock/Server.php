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
                Fields::SENDER           => [],
                Fields::TYPE             => 'push',
                Fields::STATUS           => 'created',

            ];
        }
        else
        {
            $this->validateAuthorizeInput($input);

            $override = [
                Fields::SENDER           => [
                    Fields::ADDRESS      => 'vishnu@icici',
                ],
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
                Fields::RECEIVER         => [
                    Fields::ADDRESS      => 'testmerchant@razor',
                ],
            ],
            $override);

        $this->content($content, 'authorize');

        return $this->makeJsonResponse($content);
    }

    public function refund($input)
    {
        parent::refund($input);

        if (isset($input['grant_type']) === true)
        {
            $content = [
                'access_token'  => '477131df-a649-47a7-aa64-024d1fdbddfd',
                'token_type'    => 'bearer',
                'refresh_token' => '4a669b03-3d47-40a6-ab63-15c0efcbb7db',
                'expires_in'    => 179,
            ];

            $this->content($content, 'refund_oauth');

            return $this->makeJsonResponse($content);
        }

        $data = json_decode($input, true);
        $decrypted = $this->decryptContent($data['data']);

        $content = [
            'seq_number'         => $data['seq_number'],
            'pgmerchant_Id'      => $data['pgmerchant_Id'],
            'error_code'         => '0',
            'message'            => 'Payment Successful',
            'bank_rrn'           => '824616262720',
            'responseCode'       => '00',
            'transaction_status' => 'S'
        ];

        $this->content($content, 'refund_decrypted');

        $encrypted = $this->encryptContent($content);

        $content = [
            'seq_number'    => $data['seq_number'],
            'data'          => $encrypted,
            'pgmerchant_Id' => $data['pgmerchant_Id'],
            'key_id'        => $data['key_id'],
        ];

        $this->content($content, 'refund');

        return $this->makeJsonResponse($content);
    }

    protected function encryptContent($content)
    {
        $pgp = $this->getGatewayInstance()->getPgpInstance();

        $plainText = json_encode($content);

        $encrypted = $pgp->encryptSign($plainText);

        $encrypted = str_replace("\n", '\n', $encrypted);

        return $encrypted;
    }

    protected function decryptContent($encrypted)
    {
        $encrypted = str_replace('\n', "\n", $encrypted);

        $pgp = $this->getGatewayInstance()->getPgpInstance();

        $plainText = $pgp->decryptVerify($encrypted);

        return $plainText;
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
            // Bank Fields
            Fields::CALLER_ACCOUNT_NUMBER   => '00100100100',
            Fields::CALLER_IFSC_CODE        => 'RZP10010011'
        ];

        $data = $this->getP2pEntity($override);

        $content = [
            'type'      => 'p2p_completed',
            'data'      => $data,
            'timestamp' => Carbon::now()->getTimestamp(),
        ];

        $this->content($content, 'callback');

        $raw = json_encode($content);

        $request = [
            'url'       => '/callback/upi_hulk',
            'method'    => 'post',
            'raw'       => $raw,
            'server'   => [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Hulk-Signature' => $this->getHmac($raw),
            ]
        ];

        return $request;
    }

    public function fillBharatQrNotification($qrCode = 'sqswq')
    {
        $attributes = [
            Fields::RECEIVER => [
                Fields::ID                  => 'vpa_TstMrchtVpaBqr',
                Fields::ADDRESS             => 'TstMerchantVPA.bqr@hdfcbankrzp',
            ],
            Fields::MERCHANT_REFERENCE_ID   => 'RZP' . $qrCode,
            Fields::TYPE                    => 'push',
        ];

        $data = $this->getP2pEntity($attributes);

        $content = [
            'type'      => 'bharat_qr_p2p_notify',
            'data'      => $data,
            'timestamp' => Carbon::now()->getTimestamp(),
        ];

        $raw = json_encode($content);

        $request = [
            'url'       => '/payment/callback/bharatqr/upi_hulk',
            'method'    => 'post',
            'raw'       => $raw,
            'server'    => [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Hulk-Signature' => $this->getHmac($raw),
            ]
        ];

        return $request;
    }

    public function getBharatQrValidateData()
    {
        $attributes = [
            Fields::RECEIVER => [
                Fields::ID                  => 'vpa_TstMrchtVpaBqr',
                Fields::ADDRESS             => 'TstMerchantVPA.bqr@hdfcbankrzp',
            ],
        ];

        $data = $this->getP2pEntity($attributes);

        $request = [
            'url'       => '/payment/validate/bharatqr/upi_hulk',
            'method'    => 'post',
            'content'   => $data,
            'server'    => [
                'CONTENT_TYPE'          => 'application/json',
            ]
        ];

        return $request;
    }

    protected function getP2pEntity(array $override = [])
    {
        $p2p = [
            Fields::ID                      => 'p2p_A11zpSL1413XHi',
            Fields::TXN_ID                  => 'HDF2C8B11D1FBDB4FC78F4E37A19AB6413D',
            Fields::SENDER_ID               => '9X0HrhNT68ZWeX',
            Fields::SENDER_TYPE             => 'vpa',
            Fields::RECEIVER_ID             => 'A11xBDINnz4so1',
            Fields::RECEIVER_TYPE           => 'vpa',
            Fields::STATUS                  => 'completed',
            Fields::AMOUNT                  => 50000,
            Fields::DESCRIPTION             => '',
            Fields::TYPE                    => 'pull',
            Fields::NOTES                   => [],
            Fields::CURRENCY                => 'INR',
            Fields::TRANSACTION_TYPE        => 'credit',

            // Error Fields
            Fields::ERROR_CODE              => null,
            Fields::ERROR_DESCRIPTION       => null,
            Fields::INTERNAL_ERROR_CODE     => null,

            Fields::SENDER                  => [
                'id'                        => 'vpa_9X0HrhNT68ZWeX',
                'entity'                    => 'vpa',
                Fields::ADDRESS             => 'vishnu@icici',
            ],

            Fields::RECEIVER                => [
                'id'                        => 'vpa_A11xBDINnz4so1',
                'entity'                    => 'vpa',
                Fields::ADDRESS             => 'testmerchant@razor',
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

    public static function getHmac(string $content)
    {
        return hash_hmac('sha256', $content, config('gateway.upi_hulk.gateway_terminal_password'));
    }
}
