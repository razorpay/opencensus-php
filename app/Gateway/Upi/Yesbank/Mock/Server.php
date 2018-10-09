<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Yesbank;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Yesbank\Fields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Models\Payment\Entity as Payment;

class Server extends Base\Mock\Server
{
    public function refund($input)
    {
        parent::refund($input);

        $decrypted = $this->decryptContent($input['requestMsg']);

        $this->request($decrypted, $this->action);

        $content = [
            Fields::YBLREFNO    => 'YBL' . str_random(10),
            Fields::ORDERNO     => $decrypted[Fields::ORDER_ID],
            Fields::AMOUNT      => $decrypted[Fields::AMOUNT],
            Fields::DATE        => Carbon::now()->toIso8601String(),
            Fields::STATUSCODE  => 'S',
            Fields::STATUSDESC  => 'SUCCESS',
            Fields::RESPCODE    => '00',
            Fields::APPROVALNUM => '123321',
            Fields::PRFVADDR    => 'somevpa@yesb',
            Fields::TXNID       => 'YESB0000000000000000' . str_random(15),
            Fields::RRN         => $encrypted[Fields::RRN],
            Fields::PRACCNO     => '000390100000202',
            Fields::PRIFSC      => 'YESB0000009',
            Fields::PRACCNAME   => 'Some',
            Fields::ERRORCODE   => 'NA',
            Fields::RESPERRORCODE  => 'NA',
            Fields::TRANSFERTYPE    => 'UPI',

        ];

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

        $plainText = $this->getGatewayInstance()->decryptString($encrypted);

        //$plainText = $pgp->decryptVerify($encrypted);

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

    public function getAsyncCallbackContent(Upi $upi, Payment $payment)
    {
        $content = [
            'orderno'       => $upi['payment_id'],
            'amount'        => $upi['amount'],
            'statuscode'    => 'S',
            'rrn'           => random_integer(12),
            'txnid'         => str_random(35),
        ];

        $this->content($content, 'callback');

        $raw = json_encode($content);

        $request = [
            'url'       => '/callback/upi_yesbank',
            'method'    => 'post',
            'raw'       => $raw,
            'server'   => [
                'CONTENT_TYPE'          => 'application/json',
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
            'url'       => '/payment/callback/bharatqr/upi_Yesbank',
            'method'    => 'post',
            'raw'       => $raw,
            'server'    => [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Yesbank-Signature' => $this->getHmac($raw),
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
            'url'       => '/payment/validate/bharatqr/upi_Yesbank',
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
        return hash_hmac('sha256', $content, config('gateway.upi_Yesbank.gateway_terminal_password'));
    }
}
