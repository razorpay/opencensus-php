<?php

namespace RZP\Gateway\Netbanking\Canara;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_canara';

    protected $sortRequestContent = false;

    protected $bank = 'canara';

    const CHECKSUM_ATTRIBUTE = ResponseFields::CHECKSUM;

    protected $map = [
        RequestFields::MERCHANT_CODE           => NetbankingEntity::MERCHANT_CODE,
        RequestFields::AMOUNT                  => NetbankingEntity::AMOUNT,
        RequestFields::DATE                    => NetbankingEntity::DATE,
        ResponseFields::BANK_REFERENCE_NUMBER  => NetbankingEntity::BANK_PAYMENT_ID,
        NetbankingEntity::RECEIVED             => NetbankingEntity::RECEIVED,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getRequestData($input);

        $this->createGatewayPaymentEntity($content);

        $request = $this->createRequest($content);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_REQUEST, $content);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->preProcessServerCallback($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId($input['payment']['id'], $content[ResponseFields::PAYMENT_ID]);

        $this->assertAmount(
            $this->formatAmount($input['payment']['amount'] / 100),
            $content[ResponseFields::AMOUNT]
        );

        $this->verifySecureHash($content);

        $this->checkCallbackStatus($content);

        $gatewayPayment = $this->saveCallbackResponse($content, $input['payment']);

        $this->verifyCallback($input, $gatewayPayment);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input,$acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);

    }

    // -------------------------- Authorise helper methods ------------------------------

    protected function getRequestData($input)
    {
        $merchantName = substr($input['merchant']->getFilteredDba(), 0, 8);

        $date = $this->getFormatedDate($input['payment'][Payment\Entity::CREATED_AT]);

        $amount = $this->formatAmount($input['payment'][Payment\Entity::AMOUNT] / 100);

        $fee = $this->formatAmount($input['payment_fee'] / 100);

        $data = [
            RequestFields::MODE_OF_TRANSACTION           => TransactionType::AUTHORIZE,
            RequestFields::CLIENT_CODE                   => Constants::CLIENT_CODE,
            RequestFields::CLIENT_ACCOUNT                => '', //keeping this blank as specified in the Doc
            RequestFields::MERCHANT_CODE                 => $this->getMerchantId(),
            RequestFields::CURRENCY                      => Constants::CURRENCY,
            RequestFields::AMOUNT                        => $amount,
            RequestFields::SERVICE_CHARGE                => 0,
            RequestFields::PAYMENT_ID                    => $input['payment'][Payment\Entity::ID],
            RequestFields::SUCCESS_STATIC_FLAG           => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            RequestFields::FAILURE_STATIC_FLAG           => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            RequestFields::DATE                          => $date,
            RequestFields::FLDREF1                       => $merchantName,
            RequestFields::FLDREF2                       => $fee,
        ];

        return $data;
    }

    protected function createRequest($content)
    {
        $request = $this->getStandardRequestArray();

        $checksum = $this->generateHash($content);

        $content = urldecode(http_build_query($content));

        $queryString = $content . '&checksum=' . $checksum;

        $encrypted = $this->encryptString($queryString);

        $request['url'] .= '?' . RequestFields::ENCRYPTED_DATA . '=' . $encrypted;

        return $request;
    }

    public function getMerchantId()  // TODO:: Merchant id has to be added, MID not recieeved from bank
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function getStringToHash($content, $glue = '')
    {
        return urldecode(http_build_query($content));
    }

    protected function getHashOfString($str)
    {
        return strtoupper(hash(HashAlgo::SHA256, $str));
    }

    protected function encryptString($content)
    {
        $encryptor = new AESCrypto($this->config);

        return $encryptor->encryptString($content);
    }

    public function decryptString(string $encryptedString): string
    {
        $aes = new AESCrypto($this->config);

        return $aes->decryptString($encryptedString);
    }

    // -------------------------- Callback helper methods ------------------------------

    protected function checkCallbackStatus(array $content)
    {
        if (isset($content[ResponseFields::BANK_REFERENCE_NUMBER]) === false)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'paymentid' => $content[ResponseFields::PAYMENT_ID],
                    'gateway'   => $this->gateway,
                    'content'   => $content,
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function saveCallbackResponse($content, $payment)
    {
        $content[NetbankingEntity::RECEIVED] = true;

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                                                        $payment['id'],
                                                    Action::AUTHORIZE);

        $gatewayPayment = $this->updateGatewayPaymentEntity($gatewayPayment, $content);

        return $gatewayPayment;
    }

    public function preProcessServerCallback($input): array
    {
        $encryptedString = $input[ResponseFields::ENCRYPTED_DATA];

        $decryptedString = $this->decryptString($encryptedString);

        $inputArray = [];

        $input  = explode('&', $decryptedString);

        foreach ($input as $pair)
        {
            list($key, $value) = explode('=', $pair);

            $inputArray[$key] = $value;
        }

        return $inputArray;
    }

    public function getPaymentIdFromServerCallback($input)
    {
        return $input[ResponseFields::PAYMENT_ID];
    }

    // -------------------------- Verify helper methods ------------------------------

    protected function verifyCallback($input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $request = $this->getVerifyRequest($verify);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponseContent = $this->parseResponseXml($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'          => $this->gateway,
                'raw_response'     => $response->body,
                'decoded_response' => $verify->verifyResponseContent,
                'payment_id'       => $verify->input['payment']['id'],
            ]
        );
    }

    protected function getVerifyRequest($verify)
    {
        $input = $verify->input;

        $bankRefNumber = $verify->payment['bank_payment_id'];

        $paymentEntity = $input['payment'];

        $date = $this->getFormatedDate($paymentEntity[PaymentEntity::CREATED_AT]);

        $data = [
            RequestFields::MODE_OF_TRANSACTION           => TransactionType::VERIFY,
            RequestFields::CLIENT_CODE                   => Constants::CLIENT_CODE,
            RequestFields::CLIENT_ACCOUNT                => '',
            RequestFields::MERCHANT_CODE                 => $this->getMerchantId(),
            RequestFields::CURRENCY                      => Constants::CURRENCY,
            RequestFields::AMOUNT                        => $this->formatAmount($paymentEntity[PaymentEntity::AMOUNT] / 100), // have to verify
            RequestFields::SERVICE_CHARGE                => 0,
            RequestFields::PAYMENT_ID                    => $paymentEntity[PaymentEntity::ID],
            RequestFields::SUCCESS_STATIC_FLAG           => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            RequestFields::FAILURE_STATIC_FLAG           => Constants::SUCCESS_AND_FAILURE_STATIC_FLAG,
            RequestFields::VER_DATE                      => $this->getCurrentDate(),
            RequestFields::PUR_DATE                      => $date,
        ];

        if(isset($bankRefNumber) === true)
        {
            $data[ResponseFields::BANK_REFERENCE_NUMBER] = $bankRefNumber;
        }

        $request = $this->getStandardRequestArray($data, 'post', Action::VERIFY);

        // Since they don't have a valid SSL certificate on UAT site.
        if ($this->mode === Mode::TEST)
        {
            $request['options']['verify'] = false;
        }

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'           => $this->gateway,
                'request'           => $request,
                'payment_id'        => $paymentEntity[PaymentEntity::ID],
            ]
        );

        return $request;
    }

    public function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->amountMismatch = $this->getVerifyAmountMismatch($verify);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyStatus(Verify $verify) :string
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function saveVerifyContent(Verify $verify): Base\Entity
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributes($content);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $response = $verify->verifyResponseContent;

        if (($response[ResponseFields::STATUS][ResponseFields::RETURN_CODE] === Constants::SUCCESS) and
            (isset($response[ResponseFields::STATUS][ResponseFields::VERIFY_STATUS]) === true) and
            ($response[ResponseFields::STATUS][ResponseFields::VERIFY_STATUS] === Constants::SUCCESS_VERIFY_STATUS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyAttributes(array $content): array
    {
        $data = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::STATUS][ResponseFields::RETURN_CODE], //TODO should this be converted to success/failure?
        ];

        if (empty($content[ResponseFields::VER_BANK_REFERENCE_NUMBER]) === false)
        {
            $data[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::VER_BANK_REFERENCE_NUMBER];
        }

        return $data;
    }

    protected function getVerifyAmountMismatch(Verify $verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        if (empty($content[ResponseFields::VER_AMOUNT]) === true)
        {
            return false;
        }

        $expectedAmount = $this->formatAmount($input['payment']['amount'] / 100);

        $actualAmount = $this->formatAmount($content[ResponseFields::VER_AMOUNT]);

        return ($expectedAmount !== $actualAmount);
    }



    // -------------------------- General helper methods --------------------------

    public function getFormatedDate($created_at)
    {
        return $date = Carbon::createFromTimestamp($created_at, Timezone::IST)
                             ->format('d/m/Y+H:i:s');
    }

    public function getCurrentDate()
    {
        return $date = Carbon::now(Timezone::IST)->format('d/m/Y+H:i:s');
    }

    protected function parseResponseXml(string $response): array
    {
        $response = (array) simplexml_load_string(trim($response));

        return json_decode(json_encode($response), true);
    }

    public function formatAmount($amount)
    {
        return number_format($amount, 2, '.', '');
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST,
        array $content = [])
    {
        $this->trace->info(
            $traceCode,
            [
                'request'    => $request,
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'data'       => $content
            ]);
    }
}
