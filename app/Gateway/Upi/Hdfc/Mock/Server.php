<?php

namespace RZP\Gateway\Upi\Hdfc\Mock;

use App;
use Carbon\Carbon;
use Gateway\Upi\Hdfc;
use phpseclib\Crypt\AES;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Base\Entity as UPIEntity;
use Models\Payment;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = $this->parseInput($input);

        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $content = array(
            // Razorpay Payment Id
            $input[1],
            // Bank Payment Id
            random_int(100000, 999999),
            // Amount
            $input[3],
            'SUCCESS',
            'Transaction Collect request initiated successfully',
            'nemomobile@imobile',
            'razorpay@hdfcbank',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
        );

        $this->content($content);

        return $this->makeResponse($content);
    }

    public function verify($input)
    {
        $input = $this->parseInput($input);

        parent::verify($input);

        $this->validateActionInput($input);

        $app = App::getFacadeRoot();

        $payment = $app['repo']->payment->find($input['merchantTranId']);

        $status = 'SUCCESS';
        $message = 'Transaction Successful';

        if (isset($payment['notes']['status']) === true)
        {
            if ($payment['notes']['status'] === 'created')
            {
                $status = 'PENDING';
                $message = 'Transaction Initiated';
            }
            else if ($payment['notes']['status'] === 'failed')
            {
                $status = 'FAILURE';
                $message = 'Transaction failed';
            }
        }

        $response = array(
            'response'          => '0',
            'merchantId'        => $input['merchantId'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'success'           => 'true',
            'message'           => $message,
            'merchantTranId'    => $input['merchantTranId'],
            'OriginalBankRRN'   => (string) mt_rand(1111111, 9999999),
            'status'            => $status
        );

        return $this->makeResponse($response);
    }

    /**
     * We are testing if our gateway works
     * with all possible values of error codes
     * @return int response code
     * @see HDFC Documentation:
     *
     * >All other values of response codes = Transaction has failed
     */
    protected function getResponseCode()
    {
        switch($this->input['payerVa'])
        {
            // Just make sure that this doesn't return 92
            case 'unknownresponse@hdfcbank':
                return mt_rand(93, 500);
                break;
            default:
                return 92;
        }
    }

    protected function makeResponse($data)
    {
        $content = implode('|', $data);

        $content = strtoupper(bin2hex($this->encrypt($content)));

        $response = parent::makeResponse($content);

        $response->headers->set('Content-Type', 'text/plain;charset=ISO-8859-1');
        $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        $response->headers->set('x-frame-options', 'SAMEORIGIN');

        return $response;
    }

    protected function parseInput($input)
    {
        $input = json_decode($input, true);

        $encryptedInput = $input['requestMsg'];

        $res = $this->decrypt($encryptedInput);

        $arr = explode('|', $res);

        return array_slice($arr, 0, 7);
    }

    public function decrypt($data)
    {
        $cipher = $this->getAESInstance();

        return $cipher->decrypt(hex2bin($data));
    }

    protected function encrypt($plaintext)
    {
        $cipher = $this->getAESInstance();

        return $cipher->encrypt($plaintext);
    }

    public function getAsyncCallbackContent(array $upiEntity, array $payment)
    {
        $content = $this->S2SRequestContent($upiEntity, $payment);

        $json = json_encode($content, JSON_PRETTY_PRINT);

        $encrypted = $this->encrypt($json);

        return base64_encode($encrypted);
    }

    protected function S2SRequestContent(array $upiEntity, array $payment)
    {
        // Format is 20160830152240
        $initDate = Carbon::createFromTimestampUTC($upiEntity['created_at'], 'Asia/Kolkata');
        $completeDate = $initDate->copy()->addMinutes(1);

        $response = [
            'merchantId'        => $upiEntity['gateway_merchant_id'],
            'subMerchantId'     => '1234',
            'terminalId'        => '1234',
            'BankRRN'           => $upiEntity['gateway_payment_id'],
            'merchantTranId'    => $upiEntity['payment_id'],
            'PayerName'         => 'payer name not available',
            'PayerMobile'       => $payment['contact'],
            'PayerVA'           => $upiEntity['vpa'],
            'PayerAmount'       => number_format($payment['amount']/100, 2, '.', ''),
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => $initDate->format('Ymdhis'),
            'TxnCompletionDate' => $completeDate->format('Ymdhis'),
        ];

        $this->content($response);

        return $response;
    }

    protected function getAESInstance()
    {
        $cipher = new AES(AES::MODE_ECB);

        $cipher->setKey($this->getEncryptionKey());

        return $cipher;
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_hdfc.test_merchant_key');

        return hex2bin($key);
    }
}
