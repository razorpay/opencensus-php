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

    const NA = 'na';

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
        RequestFields::CHALLAN_NUMBER  => Base\Entity::PAYMENT_ID,
        RequestFields::ITEM_CODE       => Base\Entity::CAPS_PAYMENT_ID,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $entityAttrs = $this->getEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttrs);

        $content = $this->getRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway_response' => $input[Payment\Entity::GATEWAY],
                'payment_id'       => $input['payment'][Payment\Entity::ID],
            ]
        );

        $content = $this->getDataFromCallbackResponse($input[Payment\Entity::GATEWAY]);

        $this->assertPaymentId($input['payment'][Payment\Entity::ID],
             $content[ResponseFields::CHALLAN_NUMBER]);

        $this->saveCallbackResponse($content);

        $this->checkCallbackStatus($content);

        //
        // Note : we run `verifyCallback` flow only if
        //        the response from bank is not in encrypted format
        //        e.g. : RBL , Federal bank
        //

        return $this->getCallbackResponseData($input);
    }

    /**
     * We run the 'verify' flow to remove anomalies from our system
     * e.g : if we mark a payment as failed,
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

        $verify = new Verify($this->gateway, $input);

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
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request
        );

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment'][Payment\Entity::ID],
            ]
        );

        $response = json_decode($response->body, true);

        $verify->verifyResponseContent = $this->parseVerifyResponse($response);
    }

    /**
     * We get verify response content,
     * check if the status of verify matches.
     * and save the verify content if applicable.
     *
     * @param  Verify $verify
     * @return void
     */
    public function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    /**
     * Encrypts the query string using AES256Key provided by bank.
     *
     * @param  string $queryString
     * @return string $encryptedString
     */
    public function encryptString(string $queryString): string
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $encryptedString = $crypto->encryptString($queryString);

        return $encryptedString;
    }

    /**
     * Decrypts the encrypted string using AES256Key provided by bank.
     *
     * @param  string $encryptedString
     * @return string $decryptedString
     */
    public function decryptString(string $encryptedString): string
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $decryptedString = $crypto->decryptString($encryptedString);

        return $decryptedString;
    }

    /**
     * Attributes required to create gateway entity.
     * We only need amount, as the other fields are picked up
     * from the payment array.
     *
     * @param  array $input
     * @return array
     */
    protected function getEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::MERCHANT_AMOUNT => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
            RequestFields::CHALLAN_NUMBER  => $input['payment'][Payment\Entity::ID],
            RequestFields::ITEM_CODE       => strtoupper($input['payment'][Payment\Entity::ID])
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
    protected function getRequestData(array $input): array
    {
        $encdata = $this->getHashOfArray($input);

        return [RequestFields::ENCDATA => $encdata];
    }

    /**
     * First, we create request data from payment array,
     * this has to a string with appropriate params, delimited by '|'.
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
     * All the keys are necessary to be set.
     * In case, the data is not present, they can be set to empty string, not null.
     * Failing this, the bank rejects the request.
     *
     * @param  array  $input
     * @return string $dataString
     */
    protected function createDefaultRequestData(array $input, string $glue): string
    {
        $amount = $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]);

        // date has to be of format DDMMYYYY-24HHMMSS
        $date = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT],
                                           'Asia/Kolkata')
                                           ->format('dmY-His');

        $paymentId = $input['payment']['id'];

        $merchantDetail = $input['merchant']->merchantDetail;

        $data = [
            RequestFields::USER_NAME       => $merchantDetail->getContactName(),
            RequestFields::EMAIL           => $merchantDetail->getContactEmail(),
            RequestFields::ADDRESS         => $merchantDetail->getBusinessRegisteredAddress() ?: '',
            RequestFields::PHONE_NUMBER    => $merchantDetail->getContactMobile(),
            RequestFields::CHALLAN_NUMBER  => $paymentId,
            RequestFields::MERCHANT_DATE   => $date,
            RequestFields::MERCHANT_AMOUNT => $amount,
            RequestFields::ITEM_CODE       => strtoupper($paymentId),
            RequestFields::REMARK          => '',
        ];

        if ($this->action === Action::AUTHORIZE)
        {
            $data[RequestFields::RETURN_URL] = $input['callbackUrl'];
        }
        else
        {
            $data[RequestFields::RETURN_URL] = self::NA;
        }

        $dataString = urldecode(http_build_query($data, null, $glue));

        return $dataString;
    }

    /**
     * Uses MD5 Algorithm to calculate checksum of given string.
     * Adds the checksum string and key to existing data.
     *
     * @param  string $data
     * @return string $data
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
     * @param  int [amount in paise (100)] $amount
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

        $this->checkDecryptionFailure($decryptedString, $encryptedString);

        $response = $this->formatDecryptedResponseString($decryptedString);

        return $response;
    }

    /**
     * Checks if the decryptedString is null after decryption
     * If yes, then the decryption has failed, and we trace the error
     *
     * @param string $decryptedString
     * @param string $encryptedString
     * @return void
     */
    protected function checkDecryptionFailure(
        string $decryptedString, string $encryptedString)
    {
        if (empty($decryptedString) === true)
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
                    'content'    => $content,
                    'payment_id' => $content[ResponseFields::CHALLAN_NUMBER]
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
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
        $data = $this->getRequestData($verify->input);

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

        $this->checkDecryptionFailure($decryptedString, $encryptedString);

        $response = $this->formatDecryptedResponseString($decryptedString);

        return $response;
    }

    /**
     * The decrypted string is in the format :
     * "key1=value1|key2=value2|key3=value3"
     *
     * This is a helper function to parse decrypted string to array.
     * We first replace the delimiter '|' with '&'.
     * This is by assumption that checksum & cin will not contain
     * '&' char because md5 only works on alphanum.
     * Then we use parse_str to obtains, resultant array.
     *
     * @param string $decryptedString
     * @return array $decryptedData
     */
    protected function formatDecryptedResponseString(string $decryptedString): array
    {
        $search = '|';

        $replace = '&';

        $decryptedString = str_replace($search, $replace, $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

    /**
     * This function basically checks if
     * apiSuccess & gatewaySuccess status match.
     * They are both set as either true/false.
     * Basis comparison, it returns the verifyStatus
     *
     * @param Verify  $verify
     * @return string $status
     */
    protected function getVerifyStatus(Verify $verify) :string
    {
        $response = $verify->verifyResponseContent;

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

        if (($input['payment'][Payment\Entity::STATUS] === Payment\Status::FAILED) or
            ($input['payment'][Payment\Entity::STATUS] === Payment\Status::CREATED))
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
     * Saves the attributes in gateway entity
     * after the verify call is made.
     * namely - bank_payment_id & status (if applicable)
     *
     * @param Verify       $verify
     * @return Base\Entity $gatewayPayment
     */
    protected function saveVerifyContent(Verify $verify): Base\Entity
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attrs = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    /**
     * Sets attributes that need to be set after verify call
     * Also checks if bank transaction id is same as that set in
     * gateway payment.
     *
     * Status should be conditionally saved iff -
     * the auth was never saved or auth status was a failure.
     * We do not save status if authorize's status is same.
     *
     * @param array       $content
     * @param Base\Entity $gatewayPayment
     * @return array      $attributes
     */
    protected function getVerifyAttributesToSave(
        array $content, Base\Entity $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::BANK_PAYMENT_STATUS];
        }

        //
        // Saving BID from Verify response only if BID from authorize hasn't been saved
        //
        if (isset($content[ResponseFields::BANK_TRANSACTION_ID]) === true)
        {
            if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
            {
                $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_TRANSACTION_ID];
            }
            else if ($gatewayPayment[Base\Entity::BANK_PAYMENT_ID] !== $content[ResponseFields::BANK_TRANSACTION_ID])
            {
                $this->trace->error(
                    TraceCode::GATEWAY_MULTIPLE_BANK_PAYMENT_IDS,
                    [
                        'authorize_bid' => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
                        'verify_bid'    => $content[ResponseFields::BANK_TRANSACTION_ID]
                    ]
                );
            }
        }

        return $attributes;
    }

    protected function getAuthSuccessStatus()
    {
        return Status::getAuthSuccessStatus();
    }
}
