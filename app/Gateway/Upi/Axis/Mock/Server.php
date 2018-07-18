<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Axis;
use RZP\Models\Payment;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Axis\Fields;
use RZP\Gateway\Upi\Axis\Action;
use RZP\Gateway\Upi\Axis\Status;

class Server extends Base\Mock\Server
{
    /**
     * How many legit (not "NA") fields
     * are expected to be parsed from the
     * actual incoming request
     */
    const REQUEST_FIELD_COUNT = [
        Action::COLLECT      => 17,
        Action::VERIFY       => 14,
        Action::REFUND       => 20,
        Action::VALIDATE_VPA => 14,
    ];

    /**
     * Total number of fields in every response
     * including the NA padding
     */
    const RESPONSE_FIELD_COUNT = [
        Action::AUTHORIZE       => 17,
        Action::VALIDATE_VPA    => 14,
        Action::VERIFY          => 21,
        Action::CALLBACK        => 21,
        Action::REFUND          => 21,
    ];

    public function __construct()
    {
        parent::__construct();

        if (defined('CRYPT_RSA_PKCS15_COMPAT') === false) {
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
        parent::authorize($input);

        $input = $this->parseInput($input);

        $vpa = $input[2];

        $this->validateAuthorizeInput($input);

        $content = [
            // Razorpay Payment Id
            $input[1],
            // Bank Payment Id
            random_int(100000, 999999),
            // Amount
            $input[3],
            Status::SUCCESS,
            // Description
            'Transaction Collect request initiated successfully',
            // Payer VA
            $vpa,
            // Payee VA
            'razorpay@hdfcbank',
        ];

        if ($vpa === 'failedcollect@hdfcbank')
        {
            $content[3] = 'FAILED';
            $content[4] = 'Transaction collect request failed';
        }

        $this->content($content);

        return $this->makeResponse($content);
    }

    public function decrypt($data)
    {
        return $this->getCipherInstance()
            ->decrypt(hex2bin($data));
    }

    protected function encrypt($plaintext)
    {
        return $this->getCipherInstance()
            ->encrypt($plaintext);
    }

    protected function getCipherInstance()
    {
        $cipher = new AES(AES::MODE_ECB);

        $cipher->setKey($this->getEncryptionKey());

        return $cipher;
    }

    public function validateVpa(string $input)
    {
        $this->action = Action::VALIDATE_VPA;

        $input = $this->parseInput($input, Action::VALIDATE_VPA);

        $this->validateActionInput($input, Action::VALIDATE_VPA);

        $content = [
            // Razorpay Payment Id
            $input[1],
            // Customer VPA
            $input[2],
            // Customer name
            'User Name',
            // Status
            Status::VPA_AVAILABLE,
            // Description
            'Customer vpa is valid',
        ];

        if ($input[2] === 'invalidvpa@hdfcbank')
        {
            $content[3] = Status::VPA_NOT_AVAILABLE;
            $content[4] = 'Customer vpa not valid';
        }

        $this->content($content, 'validate_vpa');

        return $this->makeResponse($content);
    }

    protected function parseInput($input, $action = Action::COLLECT)
    {
        $input = json_decode($input, true);

        $encryptedInput = $input['requestMsg'];

        $res = $this->decrypt($encryptedInput);

        $arr = explode('|', $res);

        $actualFieldLength = count($arr);
        $expectedFieldLength = self::REQUEST_FIELD_COUNT[$action];

        $message = $actualFieldLength . ' is not equal to expected ' . $expectedFieldLength;

        assertTrue($actualFieldLength === $expectedFieldLength, $message);

        return $arr;
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }
}