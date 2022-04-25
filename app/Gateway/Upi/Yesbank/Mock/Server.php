<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use Carbon\Carbon;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;

use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Yesbank;
use RZP\Gateway\Upi\Yesbank\Status;
use RZP\Gateway\Upi\Yesbank\Fields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payment\Entity as Payment;

class Server extends Base\Mock\Server
{
    const EXPECTED_COUNT = [
        'payout'        => 36,
        'payout_verify' => 15,
        'callback'      => 35,
    ];

    public function getCallback(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;

        $content = $this->callbackResponseContent($upiEntity, $payment);

        $this->content($content, 'callback');

        return $this->getCallbackRequest($content);
    }

    protected function getCallbackRequest($data)
    {
        $action = $this->action;

        // There are lots of empty "additional fields" in the response
        // that are currently expected to be filled with NA
        // The number of such fields depends on the request (auth|refund|etc)
        // We calculate the number of such fields and add it as a padding
        // with array_merge

        $paddingCount = self::EXPECTED_COUNT[$action] - count($data);

        $data = array_merge($data, array_fill(count($data), $paddingCount, 'NA'));

        $content = implode('|', $data);

        $content = $this->encrypt($content);

        $url = '/callback/upi_yesbank';

        $method = 'post';

        $raw = 'meRes='.$content ;

        $server = [
            'CONTENT_TYPE'  => 'application/xml',
        ];

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];
    }

    protected function callbackResponseContent(array $upiEntity, array $payment)
    {
        $status     = Status::SUCCESS;
        $respCode   = '00';
        $errorCode  = 'NA';
        $amount     = $payment['amount'];
        $statusDescription = 'Transaction Success';

        switch ($payment['description'])
        {
            case 'callback_failed_v2':
                $respCode   = 'U30';
                $errorCode   = 'U30';
                $status     = Status::FAILURE;
                $statusDescription = 'Transaction Fail';
                break;

            case 'callback_amount_mismatch_v2':
                $amount = $payment['amount'] + 100;
                break;

        }

        $content = [
            $upiEntity['gateway_payment_id'],
            $upiEntity['merchant_reference'],
            'COLLECT_AUTH',
            $amount,
            '2017:12:01 00:00:02',
            $status,
            $statusDescription,
            $respCode,
            $errorCode,
            random_integer(6), // Approval Number
            $payment['vpa'],
            'YESBBCB47DA7341F3D71E05400144FFB1C1', // NPCI Txn ID
            '107611570997', // NPCI Reference Id
            'NA', // Payer mobile number
            'NA', // Payee mobile number
            $payment['description'], // Note/Remarks provided by the Payer PSP
            '107712571269', // Unique Customer Ref. No
            'XXXXXX9999', // Payer Account No
            'YESB0000002', // Payer IFSC Code
            'RAHUL AGRAWAL ', // Payer Account Name
            'testybl@yesb', // Virtual Address of the Payer
            'YESB0000001', // Payee IFSC
            'XXXXXX8888', // Payee Account Number
            'NA', // Payee AADHAAR
            'Testcallback', // Payee Name
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA'
        ];

        return $content;
    }

    protected function getUnexpectedCallbackContent()
    {
        $status = Status::SUCCESS;
        $respCode = '00';
        $errorCode = 'NA';
        $amount = 1000;
        $statusDescription = 'UnexpectedCallback';

        $content = [
            999999999999,
            str_random(12),
            'COLLECT_AUTH',
            $amount,
            '2017:12:01 00:00:02',
            $status,
            $statusDescription,
            $respCode,
            $errorCode,
            random_integer(6), // Approval Number
            'testvpa@yesb',
            'YESBBCB47DA7341F3D71E05400144FFB1C1', // NPCI Txn ID
            '107611570997', // NPCI Reference Id
            'NA', // Payer mobile number
            'NA', // Payee mobile number
            'success', // Note/Remarks provided by the Payer PSP
            '107712571269', // Unique Customer Ref. No
            'XXXXXX9999', // Payer Account No
            'YESB0000002', // Payer IFSC Code
            'RAHUL AGRAWAL ', // Payer Account Name
            'unexpected@yesb', // Virtual Address of the Payer
            'YESB0000001', // Payee IFSC
            'XXXXXX8888', // Payee Account Number
            'NA', // Payee AADHAAR
            'Testcallback', // Payee Name
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA',
            'NA'
        ];
        return $content;
    }

    /**
     * Get unexpected callback response
     */
    public function getUnexpectedCallback()
    {
        $this->action = Action::CALLBACK;

        $content = $this->getUnexpectedCallbackContent();

        $this->content($content, 'callback');

        return $this->getCallbackRequest($content);
    }

    protected function encrypt($plaintext, $key = null)
    {
        $ciphertext = $this->getCipherInstance($key)->encrypt($plaintext);

        return strtoupper(bin2hex($ciphertext));
    }

    protected function getCipherInstance($key)
    {
        $cipher = new AES(AES::MODE_ECB);

        $k = "292893147e6dfe0f223447e2150f6063";

        $cipher->setKey($k);

        return $cipher;
    }

    public function decryptInput($input)
    {
        $inputArr = explode('=', $input['payload']);

        $encryptedInput = $inputArr[1];

        $res = $this->decrypt($encryptedInput);

        $arr = explode('|', $res);

        return $arr;
    }

    public function decrypt($data)
    {
        return $this->getCipherInstance(null)->decrypt(hex2bin($data));
    }

    /*************************************** Payout ******************************************/

    public function payout(array $input)
    {
        $requestArray = $this->parseInput($input);

        $responseArray = [
          Fields::YBLREFNO              => time() . 'YBL',
          Fields::ORDERNO               => $requestArray[1],
          Fields::AMOUNT                => $requestArray[3],
          Fields::DATE                  => '',
          Fields::STATUSCODE            => Yesbank\Status::SUCCESS,
          Fields::STATUSDESC            => 'Transaction success',
          Fields::RESPCODE              => '00',
          Fields::APPROVALNUM           => random_integer(5),
          Fields::PAYER_VPA             => 'test@vpa',
          Fields::NPCI_TXN_ID           => 'YESB38A1AF0B2B2B601CE05500000000000',
          Fields::CUST_REF_ID           => PublicEntity::generateUniqueId(),
          Fields::PAYER_ACC_NO          => '',
          Fields::PAYER_IFSC_NO         => '',
          Fields::PAYER_ACC_NAME        => '',
          Fields::ERROR_CODE            => '',
          Fields::RESPONSE_ERROR_CODE   => '',
          Fields::TRANSFER_TYPE         => 'UPI',
          Fields::PAYEE_VPA             => $requestArray[12],
          Fields::PAYEE_IFSC            => '',
          Fields::PAYEE_ACC_NO          => '',
          Fields::PAYEE_AADHAR          => '',
          Fields::PAYEE_ACC_NAME        => '',
          Fields::ADD1                  => '',
          Fields::ADD2                  => '',
          Fields::ADD3                  => '',
          Fields::ADD4                  => '',
          Fields::ADD5                  => '',
          Fields::ADD6                  => '',
          Fields::ADD7                  => '',
          Fields::ADD8                  => '',
          Fields::ADD9                  => 'NA',
          Fields::ADD10                 => 'NA',
        ];

        $this->content($responseArray, 'payout');

        $msg = implode('|' , $responseArray);

        $response = $this->getGatewayInstance()->encryptRequest($msg);

        return $this->makeResponse($response);
    }

    public function payoutVerify(array $input)
    {
        $requestArray = $this->parseInput($input, Action::PAYOUT_VERIFY);

        $responseArray = [
            Fields::YBLREFNO                => $requestArray[2],
            Fields::ORDERNO                 => $requestArray[1],
            Fields::AMOUNT                  => '1.00',
            Fields::DATE                    => '',
            Fields::STATUSCODE              => Yesbank\Status::VERIFY_SUCCESS,
            Fields::STATUSDESC              => 'Transaction success',
            Fields::RESPCODE                => '00',
            Fields::APPROVALNUM             => random_integer(5),
            Fields::PAYER_VPA               => 'test@vpa',
            Fields::NPCI_TXN_ID             => 'YESB38A1AF0B2B2B601CE05500000000000',
            Fields::REFERENCE_ID            => '',
            Fields::CUST_REF_ID             => $requestArray[3],
            Fields::PAYER_ACC_NO            => '',
            Fields::PAYER_IFSC_NO           => '',
            Fields::PAYER_ACC_NAME          => '',
            Fields::PAYEE_VPA               => '',
            Fields::PAYEE_IFSC              => '',
            Fields::PAYEE_ACC_NO            => '',
            Fields::PAYEE_AADHAR            => '',
            Fields::PAYEE_ACC_NAME          => '',
            Fields::TIMED_OUT_TXN_STATUS    => '',
            Fields::ADD1                    => '',
            Fields::ADD2                    => '',
            Fields::ADD3                    => '',
            Fields::ADD4                    => '',
            Fields::ADD5                    => '',
            Fields::ADD6                    => '',
            Fields::ADD7                    => '',
            Fields::ADD8                    => '',
            Fields::ADD9                    => 'NA',
            Fields::ADD10                   => 'NA',
        ];

        $this->content($responseArray, 'payout_verify');

        $msg = implode('|' , $responseArray);

        $response = $this->getGatewayInstance()->encryptRequest($msg);

        return $this->makeResponse($response);
    }

    protected function parseInput($input, $action = Action::PAYOUT)
    {
        $encryptedInput = $input['requestMsg'];

        $res = $this->getGatewayInstance()->decryptResponse($encryptedInput);

        $arr = explode('|', $res);

        $actualFieldLength = count($arr);

        $expectedFieldLength = self::EXPECTED_COUNT[$action];

        $message = $actualFieldLength . ' is not equal to expected ' . $expectedFieldLength;

        assertTrue($actualFieldLength === $expectedFieldLength, $message);

        return $arr;
    }
}
