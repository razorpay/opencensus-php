<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use App;
use Carbon\Carbon;
use Gateway\Upi\Axis;
use RZP\Gateway\Upi\Axis\Action;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Axis\Fields;
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
    ];

    /**
     * Total number of fields in every response
     * including the NA padding
     */
    const RESPONSE_FIELD_COUNT = [
        Action::AUTHORIZE       => 17,
        Action::VERIFY          => 21,
        Action::CALLBACK        => 21,
        Action::REFUND          => 21,
    ];

    public function authorize($input)
    {
        parent::authorize($input);

        $input = $this->parseInput($input);

        $vpa = $input['customerVpa'];

        $this->validateAuthorizeInput($input);

        $content = [
            Fields::CODE => '000',
            Fields::RESULT => 'SUCCESS',
            Fields::DATA => $this->generateRandomString(30),
        ];

        if ($vpa === 'failedcollect@hdfcbank')
        {
            $content[3] = 'FAILED';
            $content[4] = 'Transaction collect request failed';
        }

        $this->content($content);
        return $this->makeResponse($content);
    }

    protected function parseInput($input, $action = Action::COLLECT)
    {
        $input = json_decode($input, true);

        return $input;
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

        $content = json_encode($data);

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

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment);

        $this->content($content,'callback');

        $response = $this->makeResponse($content);

        return [
            'meRes' => $response->content()
        ];
    }

    protected function callbackResponseContent(array $upiEntity, array $payment)
    {
        $status = Status::SUCCESS;

        switch ($payment['vpa'])
        {
            case 'failed@hdfcbank':
                $status = Status::FAILED;
                break;
        }

        return [
            $upiEntity['gateway_payment_id'],
            $upiEntity['payment_id'],
            $this->formatAmount($payment['amount']),
            '2017:12:01 00:00:02',
            $status,
            'Transaction success',
            '00',
            // Approval Number
            random_integer(5),
            $payment['vpa'],
            // NPCI Reference Id
            random_integer(16),
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'PNB!10000000000!PNBI1111111!8966829290'
        ];
    }
    /**
     * @param  int    $amount amount in paise
     * @return string
     */
    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100 ,2, '.', '');
    }


    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}