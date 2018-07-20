<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use App;
use Carbon\Carbon;
use Gateway\Upi\Axis;
use RZP\Gateway\Upi\Axis\Action;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Axis\Status;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Base\Entity as UPIEntity;
use Models\Payment;

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

    public function authorize($input)
    {
        parent::authorize($input);

        $input = $this->parseInput($input);
        s($input);

        $vpa = $input[2];

        $this->validateAuthorizeInput($input);

        $content = [
            // Razorpay Payment Id
            $input[2],
            // Bank Payment Id
            random_int(100000, 999999),
            // Amount
            $input[4],
            '00',
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
        s($content);
        return $this->makeResponse($content);
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

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }

    /**
     * See docs link in README.md for response formatting
     */
    protected function makeResponse($data)
    {
        $action = $this->action;

        // There are lots of empty "additional fields" in the response
        // that are currently expected to be filled with NA
        // The number of such fields depends on the request (auth|refund|etc)
        // We calculate the number of such fields and add it as a padding
        // with array_merge

        $paddingCount = self::RESPONSE_FIELD_COUNT[$action] - count($data);

        $data = array_merge($data, array_fill(count($data), $paddingCount, 'NA'));

        $content = implode('|', $data);

        $content = strtoupper(bin2hex($this->encrypt($content)));

        $response = parent::makeResponse($content);

        $response->headers->set('Content-Type', 'text/plain;charset=ISO-8859-1');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        $response->headers->set('x-frame-options', 'SAMEORIGIN');

        return $response;
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
}