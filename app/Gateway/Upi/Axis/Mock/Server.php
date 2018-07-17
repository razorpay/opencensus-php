<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Axis;
use RZP\Models\Payment;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Axis\Fields;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        if (defined('CRYPT_RSA_PKCS15_COMPAT') === false)
        {
            define('CRYPT_RSA_PKCS15_COMPAT', true);
        }
    }

    /**
     * Private Key of the mock server
     */
    protected function getPrivateKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockserver.key');
    }

    /**
     * Public key of the client that is connecting
     * to us, in this case, the Mock Gateway
     */
    protected function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/keys/mockclient.pub');
    }

    public function authorize($input)
    {
        $input = $this->parseInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = [
            Fields::RESPONSE         => $this->getAuthorizeResponseCode(),
            Fields::MERCHANT_ID      => $input['merchantId'],
            Fields::SUBMERCHANT_ID   => $input['subMerchantId'] ?? null,
            Fields::TERMINAL_ID      => $input['terminalId'] ?? null,
            Fields::SUCCESS          => 'true',
            Fields::MESSAGE          => 'Transaction initiated',
            Fields::MERCHANT_TRAN_ID => $input['merchantTranId'],
            Fields::BANK_RRN         => random_int(111111111, 999999999),
        ];

        $dontEncrypt = ((isset($input['payerVa']) === true) and
            ($input['payerVa'] === 'dontencrypt@icici'));

        $this->content($content, 'authorize');

        return $this->makeResponse($content, $dontEncrypt);
    }