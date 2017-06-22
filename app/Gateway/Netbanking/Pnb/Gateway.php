<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_pnb';

    protected $bank = 'pnb';

    /**
     * This array maps the request fields of gateway
     * to generic gateway entity while creating
     * the gateway payment entity.
     *
     * To understand the usage, see method : getMappedAttributes
     * in class Netbanking\Base\Gateway
     */
    protected $map = [
        RequestFields::MERCHANT_AMOUNT => Base\Entity::AMOUNT,
        RequestFields::CIN             => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE       => Base\Entity::CAPS_PAYMENT_ID,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        // get attributes for entity
        $entityAttrs = $this->getEntityAttributes($input);

        // creates gateway payment entity
        $this->createGatewayPaymentEntity($entityAttrs);

        // gets content for bank authorization
        $content = $this->getAuthorizeRequestData($input);

        // gets request array for authorize
        $request = $this->getStandardRequestArray($content);

        // trace the request
        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        // traces the gateway response & payment id
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway_response' => $input['gateway'],
                'payment_id'       => $input['payment']['id'],
            ]
        );

        // gets content after decrypting gateway response
        $content = $this->getDataFromCallbackResponse($input['gateway']);

        // checks if payment_id is same as 'cin' in response fields
        $this->assertPaymentId($input['payment']['id'],
             $content[ResponseFields::CHALLAN_NUMBER]);

        // save date from callback's response
        $this->saveCallbackResponse($content);

        // checks callback status for success
        $this->checkCallbackStatus($content);

        //
        // Note : we run `verifyCallback` flow only if
        //        the response from bank is not in encrypted format
        //        e.g. : RBL , Federal bank
        //

        // return payment's two factor auth as passed
        return $this->getCallbackResponseData($input);
    }

    /**
     * We run the 'verify' flow to remove anomalies from our system
     * e.g : if we mark a payment as failed that payment,
     * that payment can be successful at bank’s side.
     * This can be due to timeout, or getting info late from the bank.
     * Earlier this was done via recon,
     * But now we can hit the verify api and find status of payment.
     *
     * @param $input array
     * @return array
     */
    public function verify(array $input): array
    {
        parent::verify($input);

        // create new instance of Verify with gateway & input
        $verify = new Verify($this->gateway, $input);

        // run the payment verify flow
        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * We send payment verify request to url mentioned by bank
     * And parse the response we get from their end.
     *
     * @param $verify Verify
     * @return void
     */
    public function sendPaymentVerifyRequest(Verify $verify)
    {
        // gets data for payment verify request
        $content = $this->getPaymentVerifyData($verify);

        // gets request array for verify with content
        $request = $this->getStandardRequestArray($content);

        // trace the request
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request
        );

        // send request
        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

        // response body is received as json string
        $response = json_decode($response->body, true);

        // set veirfy response content after parsing
        $verify->verifyResponseContent = $this->parseVerifyResponse($response);
    }

    /**
     * We get verify response content, match status
     * and if the status matches, we save verify content
     *
     * @param $verify Verify
     * @return void
     */
    public function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->status = $this->getVerifyStatus($verify, $content);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    /**
     * Encrypts the query string using AES256Key provided by bank.
     *
     * AES in ECB mode without padding.
     * ECB encrypts each block of data independently
     * and the same plaintext block will result in the same ciphertext block.
     *
     * @param $queryString      string
     * @return $encryptedString string
     */
    public function encryptString(string $queryString): string
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto(AES::MODE_ECB, $masterKey);

        $encryptedString = $crypto->encryptString($queryString);

        return $encryptedString;
    }

    /**
     * Decrypts the encrypted string using AES256Key provided by bank.
     *
     * AES in ECB mode wihout padding.
     * ECB encrypts each block of data independently
     * and the same plaintext block will result in the same ciphertext block.
     *
     * @param $encryptedString  string
     * @return $decryptedString string
     */
    public function decryptString(string $encryptedString): string
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto(AES::MODE_ECB, $masterKey);

        $decryptedString = $crypto->decryptString($encryptedString);

        return $decryptedString;
    }

    /**
     * Attributes required to create gateway entity.
     * We only need amount, as the other fields are picked up
     * from the payment array.
     *
     * @param $input array
     * @return array
     */
    protected function getEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::MERCHANT_AMOUNT => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
            RequestFields::CIN             => $input['payment']['id'],
            RequestFields::ITEM_CODE       => strtoupper($input['payment']['id'])
        ];

        return $entityAttributes;
    }

    /**
     * It uses function 'getHashOfArray' which internally uses
     * 'getStringToHash' to convert request array to string + append checksum
     * & 'getHashOfString' to encrypt the string.
     *
     * The final request data looks like =>
     * ['encdata' => some_encrypted_string]
     *
     * @param  array $input
     * @return array ['encdata' => $encData]
     */
    protected function getAuthorizeRequestData(array $input): array
    {
        $encdata = $this->getHashOfArray($input);

        return [RequestFields::ENCDATA => $encdata];
    }

    /**
     * First, we create request data from payment array,
     * this has to a string with params delimited by '|'.
     * We need to preserve this string, because after appending
     * checksum only, we encode the entire string.
     *
     * The combined string above is used to generate checksum,
     * and is added to the combined string, delimited by '|'.
     * This is represented by $dataString.
     *
     * e.g. : $data = ['cin' => "6vTX585l2WP6Bq", 'MerchantAmt' => 100]
     *        returns string "cin=6vTX585l2WP6Bq|MerchantAmt=100|checksum=checksum123"
     *
     * @param  array  $input
     * @param  string $glue delimiter
     * @return string $dataString
     */
    protected function getStringToHash($input, $glue = '|'): string
    {
        $dataString = $this->createDefaultRequestData($input, $glue);

        $dataString = $this->computeAndAppendChecksumToRequestData($dataString);

        return $dataString;
    }

    /**
     * This step encrypts the final data string.
     * Uses AES 128 bit / 256 bit to encrypt the string.
     * This is the final value of request content.
     *
     * @param  string $data
     * @return string $encdata
     */
    protected function getHashOfString($data): string
    {
        $encdata = $this->encryptString($data);

        return $encdata;
    }

    /**
     * Creates the request data string delimited by '|'
     *
     * First, it creates the array with keys & corresponding value.
     * Then uses 'http_build_query' to create the string.
     * Not using 'implode' because need to preserve keys.
     *
     * @param  array  $input
     * @return string $dataString
     */
    protected function createDefaultRequestData(array $input, string $glue): string
    {
        $amount = $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]);

        // date has to be of format DDMMYYYY-24HHMMSS
        $date = Carbon::createFromTimestamp($input['payment']['created_at'],
                                           'Asia/Kolkata')
                                           ->format('dmY-His');

        $paymentId = $input['payment']['id'];

        $data = [
            RequestFields::CIN             => $paymentId,
            RequestFields::MERCHANT_DATE   => $date,
            RequestFields::MERCHANT_AMOUNT => $amount,
            RequestFields::ITEM_CODE       => strtoupper($paymentId),
        ];

        if ($this->action === Action::AUTHORIZE)
        {
            $data[RequestFields::RETURN_URL] = $input['callbackUrl'];
        }
        else
        {
            $data[RequestFields::RETURN_URL] = 'na';
        }

        // string http_build_query($data, $prefix, $delimiter)
        $dataString = http_build_query($data, null, $glue);

        return $dataString;
    }

    /**
     * Uses MD5 Algorithm to calculate checksum of given string.
     * Adds the checksum string and key to exitsing data.
     *
     * @param $data  string
     * @return $data string
     */
    protected function computeAndAppendChecksumToRequestData(string $data): string
    {
        $checksum = md5($data);

        $checksumData = RequestFields::CHECKSUM . '=' . $checksum;

        $data = $data . '|' . $checksumData;

        return $data;
    }

    /**
     * Formats amount to 2 decimal places
     *
     * @param  $amount int [amount in paise (100)]
     * @return string [amount formatted to 2 decimal places in INR (1.00)]
     */
    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * The data that we get as response from gateway is encrypted.
     * We need to decrypt the string using key & decryption algorithm.
     * Also check for decryption failure in any case.
     *
     * @param $encryptedResponse array
     * @return $response         array
     */
    protected function getDataFromCallbackResponse(array $encryptedResponse): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCDATA];

        $decryptedString = $this->decryptString($encryptedString);

        $this->checkDecryptionFailure($decryptedString);

        $response = $this->formatDecrytedResponseString($decryptedString);

        return $response;
    }

    /**
     * Checks if the decryptedString is null after decryption
     * If yes, then the decryption has failed, and we trace the error
     *
     * @param $decryptedString string
     * @return void
     */
    protected function checkDecryptionFailure(string $decryptedString)
    {
        if (is_null($decryptedString) === true)
        {
            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'encrypted_string' => $encryptedString
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }
    }

    /**
     * Saves the response from callback to gateway entity
     * 1. Finds payment id for action authorize
     * 2. Creates the attributes to be set in entity & saves.
     *    Attrs set are => received (bool)
     *                     status (BANK_PAYMENT_STATUS)
     *                     bank_payment_id (BANK_TRANSACTION_ID)
     *
     * @param $content array
     * @return void
     */
    protected function saveCallbackResponse(array $content)
    {
        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $content[ResponseFields::CHALLAN_NUMBER], Action::AUTHORIZE);

        $attrs = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::BANK_PAYMENT_STATUS],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_TRANSACTION_ID]
        ];

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);
    }

    /**
     * Checks if the payment status from bank is set.
     * Or if it is set to value other than 'S'
     * where 'S' stands for success.
     * If not, then we trace the error as PAYMENT_CALLBACK_FAILURE.
     *
     * @param $content array
     * @return void
     */
    protected function checkCallbackStatus(array $content)
    {
        if ((isset($content[ResponseFields::BANK_PAYMENT_STATUS]) === false) or
            ($content[ResponseFields::BANK_PAYMENT_STATUS] !== Status::SUCCESS))
        {
            $this->trace->error(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    /**
     * Gets the data to be set for verify request
     * In this case, it is the same as request data for payment
     * in other words, same the data set while authorization.
     *
     * @param $verify Verify
     * @return $data  array
     */
    protected function getPaymentVerifyData(Verify $verify): array
    {
        $input = $verify->input;

        $data = $this->getAuthorizeRequestData($input);

        return $data;
    }

    /**
     * The verify response for PNB contains the same fields
     * as that of payment request's response.
     * So we decrypt the encrypted response & parse string and return
     *
     * @param  array $encryptedResponse
     * @return array $response
     */
    protected function parseVerifyResponse(array $encryptedResponse): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCDATA];

        $decryptedString = $this->decryptString($encryptedString);

        $response = $this->formatDecrytedResponseString($decryptedString);

        return $response;
    }

    /**
     * The decrypted string is in the format :
     * "key1=value1|key2=value2|key3=value3"
     * So the one-step functions like, simple explode or parse_str
     * were not being useful here.
     *
     * This is a helper function to parse decrypted string to array.
     * We first explode array by delimiter '|' to get all pairs.
     * Then we iterate over all those pairs, explode them by delim '=',
     * and store the key-val in a 'result' array.
     *
     * @param $decryptedString string
     * @return $result         array
     */
    protected function formatDecrytedResponseString(string $decryptedString): array
    {
        $result = [];

        $allPairs = explode('|' , $decryptedString);

        foreach ($allPairs as $pairString)
        {
            $pair = explode('=', $pairString);

            // $pair[0] => 'key' , $pair[1] => 'value'
            $result[$pair[0]] = $pair[1];
        }

        return $result;
    }

    /**
     * This function basically checks if
     * apiSuccess & gatewaySuccess status match.
     * They are both set as either true/false.
     * Basis comparison, it returns the verifyStatus
     *
     * @param $verify   Verify
     * @param $response array
     * @return $status  string
     */
    protected function getVerifyStatus(Verify $verify, array $response) :string
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify, $response);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    /**
     * Checks if the payment status is 'failed' or 'created'.
     * If it's neither, then the status is set to false.
     *
     * @param $verify Verify
     * @return void
     */
    protected function checkApiSuccess(Verify $verify)
    {
        $verify->apiSuccess = true;

        $input = $verify->input;

        if (($input['payment']['status'] === Payment\Status::FAILED) or
            ($input['payment']['status'] === Payment\Status::CREATED))
        {
            $verify->apiSuccess = false;
        }
    }

    /**
     * Checks for success from gateway response
     * if it is set as 'S', then it sets 'gatewaySuccess' as true
     *
     * @param $verify Verify
     * @return void
     */
    protected function checkGatewaySuccess(Verify $verify)
    {
        $response = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($response[ResponseFields::BANK_PAYMENT_STATUS]) === true) and
            ($response[ResponseFields::BANK_PAYMENT_STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    /**
     * Saves the 'received' & 'status' attribute in gateway entity
     * after the verify call is made.
     *
     * @param $verify          Verify
     * @return $gatewayPayment Base\Entity
     */
    protected function saveVerifyContent(Verify $verify): Base\Entity
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attrs = [
            Base\Entity::RECEIVED => true,
            Base\Entity::STATUS   => $content[ResponseFields::BANK_PAYMENT_STATUS],
        ];

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }
}
