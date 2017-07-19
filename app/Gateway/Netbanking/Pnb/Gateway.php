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

        return $this->getCallbackResponseData($input);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

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

    public function verifyPayment(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);
    }

    public function encryptString(string $queryString): string
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $encryptedString = $crypto->encryptString($queryString);

        return $encryptedString;
    }

    public function decryptString(string $encryptedString): string
    {
        $masterKey = $this->getSecret();

        $crypto = new AESCrypto($masterKey);

        $decryptedString = $crypto->decryptString($encryptedString);

        return $decryptedString;
    }

    protected function getEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::MERCHANT_AMOUNT => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
            RequestFields::CHALLAN_NUMBER  => $input['payment'][Payment\Entity::ID],
            RequestFields::ITEM_CODE       => strtoupper($input['payment'][Payment\Entity::ID])
        ];

        return $entityAttributes;
    }


    protected function getRequestData(array $input): array
    {
        $encdata = $this->getHashOfArray($input);

        return [RequestFields::ENCDATA => $encdata];
    }

    protected function getStringToHash($input, $glue = '|'): string
    {
        $dataString = $this->createDefaultRequestData($input, $glue);

        $dataString = $this->computeAndAppendChecksumToRequestData($dataString);

        return $dataString;
    }

    protected function getHashOfString($data): string
    {
        $encdata = $this->encryptString($data);

        return $encdata;
    }

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

    protected function computeAndAppendChecksumToRequestData(string $data): string
    {
        $checksum = md5($data);

        $checksumData = RequestFields::CHECKSUM . '=' . $checksum;

        $data = $data . '|' . $checksumData;

        return $data;
    }

    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getDataFromCallbackResponse(array $encryptedResponse): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCDATA];

        $decryptedString = $this->decryptString($encryptedString);

        $this->checkDecryptionFailure($decryptedString, $encryptedString);

        $response = $this->formatDecryptedResponseString($decryptedString);

        return $response;
    }

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

    protected function getPaymentVerifyData(Verify $verify): array
    {
        $data = $this->getRequestData($verify->input);

        return $data;
    }

    protected function parseVerifyResponse(array $encryptedResponse): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCDATA];

        $decryptedString = $this->decryptString($encryptedString);

        $this->checkDecryptionFailure($decryptedString, $encryptedString);

        $response = $this->formatDecryptedResponseString($decryptedString);

        return $response;
    }

    protected function formatDecryptedResponseString(string $decryptedString): array
    {
        $decryptedString = str_replace('|', '&', $decryptedString);

        parse_str($decryptedString, $decryptedData);

        return $decryptedData;
    }

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

    protected function saveVerifyContent(Verify $verify): Base\Entity
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attrs = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(
        array $content, Base\Entity $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::BANK_PAYMENT_STATUS];
        }

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
        return Status::SUCCESS;
    }
}
