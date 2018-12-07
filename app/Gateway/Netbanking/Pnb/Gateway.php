<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Netbanking\Base;
use RZP\Exception\LogicException;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Processor\Netbanking;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'netbanking_pnb';

    const CHECKSUM_ATTRIBUTE = RequestFields::CHECKSUM;

    protected $bank = 'pnb';

    protected $map = [
        RequestFields::AMOUNT      => Base\Entity::AMOUNT,
        RequestFields::PAYMENT_ID  => Base\Entity::PAYMENT_ID,
    ];

    public function authorize(array $input): array
    {
        parent::authorize($input);

        $entityAttrs = $this->getNetbankingEntityAttributes($input);

        $this->createGatewayPaymentEntity($entityAttrs);

        $content = $this->getAuthorizeRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input): array
    {
        parent::callback($input);

        $content = $this->getDataFromCallbackResponse($input[Payment\Entity::GATEWAY],
                                                      $input['payment']['id']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'            => $this->gateway,
                'gateway_response'   => $input[Payment\Entity::GATEWAY],
                'payment_id'         => $input['payment'][Payment\Entity::ID],
                'decrypted response' => $content
            ]
        );

        $this->assertPaymentId($input['payment'][Payment\Entity::ID],
                               $content[ResponseFields::PAYMENT_ID]);

        $this->assertAmount($this->formatAmount($input['payment']['amount']),
                            $content[ResponseFields::AMOUNT]);

        $this->verifySecureHash($content);

        $this->checkCallbackStatus($content);

        $gatewayPayment = $this->saveCallbackResponse($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input): array
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $content = $this->getRefundRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request' => $request,
                'gateway' => $this->gateway
            ]
        );

        $response = $this->sendGatewayRequest($request);

        $this->processRefundResponse($response, $input);
    }

    public function sendPaymentVerifyRequest(Verify $verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment'][Payment\Entity::ID],
            ]
        );

        $verify->verifyResponseContent = $this->parseVerifyResponse($response);
    }

    public function verifyPayment(Verify $verify)
    {
        $verify->status = $this->getVerifyStatus($verify);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyContent($verify);

        if ($verify->gatewaySuccess === true)
        {
            $this->setVerifyAmountMismatch($verify);
        }
    }

    public function encryptString(string $jsonString): string
    {
        $secret = $this->getSecret();

        $encrypted = base64_encode(openssl_encrypt(
                                                   $jsonString,
                                           'AES-256-ECB',
                                                   $secret,
                                           OPENSSL_RAW_DATA
                                                   )
                                  );

        return $encrypted;
    }

    public function decryptString(string $encryptedString, $paymentId): string
    {
        $decryption_key = $this->getSecret();

        $decryptedString = openssl_decrypt(base64_decode($encryptedString),
                       'AES-256-ECB',
                               $decryption_key,
                       OPENSSL_RAW_DATA);

        $this->checkDecryptionFailure($decryptedString, $encryptedString, $paymentId);

        return $decryptedString;
    }

    protected function checkDecryptionFailure(
        string $decryptedString, string $encryptedString, $paymentId)
    {
        if (empty($decryptedString) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                '',
                '',
                [
                    'encrypted string' => $encryptedString,
                    'gateway'          => $this->gateway,
                    'payment_id'       => $paymentId
                ]
            );
        }
    }

    protected function getNetbankingEntityAttributes(array $input): array
    {
        $entityAttributes = [
            RequestFields::AMOUNT      => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
            RequestFields::PAYMENT_ID  => $input['payment'][Payment\Entity::ID],
        ];

        return $entityAttributes;
    }

    protected function getAuthorizeRequestData(array $input): array
    {
        $content = $this->getRequestContentData($input);

        $content[RequestFields::CHECKSUM] = $this->generateHash($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'gateway'        => $this->gateway,
                'payment_id'     => $input['payment']['id'],
                'decrypted_data' => $content,
            ]);

        $contentAsJsonString = json_encode($content);

        $encrypted = $this->encryptString($contentAsJsonString);

        return [
            RequestFields::API_KEY        => $content[RequestFields::API_KEY],
            RequestFields::ENCRYPTED_DATA => $encrypted
        ];
    }

    protected function getRequestContentData(array $input): array
    {
        $data = [
            RequestFields::API_KEY        => $this->getMerchantId(),
            RequestFields::ADDRESS_LINE_1 => Constants::RZP_ADDRESS_LINE_1,
            RequestFields::ADDRESS_LINE_2 => Constants::RZP_ADDRESS_LINE_2,
            RequestFields::AMOUNT         => $this->formatAmount($input['payment'][Payment\Entity::AMOUNT]),
            RequestFields::CITY           => Constants::CITY,
            RequestFields::COUNTRY        => Constants::COUNTRY,
            RequestFields::CURRENCY       => Constants::INDIAN_RUPEE,
            RequestFields::DESCRIPTION    => Constants::RZP_NAME, //TODO find what to send here
            RequestFields::EMAIL          => Constants::RZP_EMAIL,
            RequestFields::MODE           => 'LIVE',
            RequestFields::NAME           => Constants::RZP_NAME,
            RequestFields::PAYMENT_ID     => $input['payment']['id'],
            RequestFields::PHONE          => Constants::RZP_PHONE,
            RequestFields::RETURN_URL     => $input['callbackUrl'],
            RequestFields::STATE          => Constants::STATE,
            RequestFields::ZIP_CODE       => Constants::ZIP_CODE,
        ];

        if ($input['payment']['bank'] === Netbanking::PUNB_R)
        {
            $data[RequestFields::BANK_CODE] = Constants::BANK_CODE_RETAIL;
        }
        else
        {
            $data[RequestFields::BANK_CODE] = Constants::BANK_CODE_CORPORATE;
        }

        return $data;
    }

    protected function formatAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getDataFromCallbackResponse(array $encryptedResponse, $paymentId): array
    {
        $encryptedString = $encryptedResponse[ResponseFields::ENCRYPTED_DATA];

        $decryptedString = $this->decryptString($encryptedString, $paymentId);

        return $this->jsonToArray($decryptedString);
    }

    protected function saveCallbackResponse(array $content)
    {
        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $content[ResponseFields::PAYMENT_ID],
            Action::AUTHORIZE
        );

        $attrs = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::STATUS          => $content[ResponseFields::RESPONSE_CODE],
            Base\Entity::BANK_PAYMENT_ID => $content[ResponseFields::BANK_PAYMENT_ID]
        ];

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        return $gatewayEntity;
    }

    protected function checkCallbackStatus(array $content)
    {
        if (Status::isSuccess($content[ResponseFields::RESPONSE_CODE]) === true)
        {
            return;
        }

        $internalErrorCode = ErrorCodes::getErrorCodeMap($content[ResponseFields::RESPONSE_CODE]);

        $gatewayErrorDesc = $content[ResponseFields::ERROR_DESC] ?? '';

        throw new Exception\GatewayErrorException(
            $internalErrorCode,
            $content[ResponseFields::RESPONSE_CODE],
            $gatewayErrorDesc,
            $content
        );
    }

    protected function getVerifyRequestData(Verify $verify): array
    {
        $input = $verify->input;

        $gateway = $verify->payment;

        $content = [
            RequestFields::API_KEY          => $this->getMerchantId(),
            RequestFields::PAYMENT_ID       => $input['payment']['id'],
            ResponseFields::BANK_PAYMENT_ID => $gateway->getBankPaymentId() ?? '',
            ResponseFields::RESPONSE_CODE   => $gateway->getStatus() ?? '',
        ];

        if ($input['payment']['bank'] === Netbanking::PUNB_R)
        {
            $content[RequestFields::BANK_CODE] = Constants::BANK_CODE_RETAIL;
        }
        else
        {
            $content[RequestFields::BANK_CODE] = Constants::BANK_CODE_CORPORATE;
        }

        $content[RequestFields::CHECKSUM] = $this->generateHash($content);

        return $content;
    }

    protected function getRefundRequestData($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $bankPaymentId = $gatewayPayment[Base\Entity::BANK_PAYMENT_ID];

        $data = [
            RequestFields::API_KEY         => $this->getMerchantId(),
            RequestFields::BANK_PAYMENT_ID => $bankPaymentId,
            RequestFields::AMOUNT          => $this->formatAmount($input['refund']['amount']),
            RequestFields::DESCRIPTION     => Constants::REFUND_DESCRIPTION,
        ];

        $data[RequestFields::CHECKSUM] = $this->generateHash($data);

        return $data;
    }

    protected function processRefundResponse($response, $input)
    {
        $content = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'response' => $content,
                 'gateway' => $this->gateway
            ]);

        if (isset($content['error']) === true)
        {
            $responseArray = $content['error'];
        }
        else
        {
            $responseArray = $content['data'];
        }

        $attributes = $this->getRefundAttributes($input);

        if (isset($content['error']) === true)
        {
            $attributes[Base\Entity::ERROR_MESSAGE] = $responseArray[ResponseFields::ERROR_MESSAGE];
        }
        else
        {
            //TODO should I store transaction_id or refund_reference_no here ?
            $attributes[Base\Entity::BANK_PAYMENT_ID] = $responseArray[ResponseFields::REFUND_REFERENCE_NO];
        }

        $this->createGatewayPaymentEntity($attributes);

        $this->checkRefundStatus($content);

        $this->assertPaymentId($input['payment']['id'], $responseArray[ResponseFields::MERCHANT_ORDER_ID]);
    }

    protected function getRefundAttributes($input)
    {
        $attributes = [
            Base\Entity::RECEIVED        => true,
            Base\Entity::AMOUNT          => $input['refund']['amount'] / 100,
            Base\Entity::REFUND_ID       => $input['refund']['id'],
        ];

        return $attributes;
    }

    protected function parseVerifyResponse($response): array
    {
        $responseArray = $this->jsonToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'decrypted'  => true,
                'response'   => $responseArray,
            ]
        );

        if (isset($responseArray['data']) === true)
        {
            return $responseArray['data'][0];
        }

        return $responseArray;
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

    protected function checkRefundStatus($responseArray)
    {
        if (isset($responseArray['error']) === true)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED);
        }
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $response = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($response[ResponseFields::RESPONSE_CODE]) === true) and
            (Status::isSuccess($response[ResponseFields::RESPONSE_CODE]) === true))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function setVerifyAmountMismatch(Verify $verify)
    {
        $paymentAmount = $this->formatAmount($verify->input['payment'][Payment\Entity::AMOUNT]);

        $verify->amountMismatch =
            ($paymentAmount !== $verify->verifyResponseContent[ResponseFields::AMOUNT]);
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attrs = $this->getVerifyAttributesToSave($content, $gatewayPayment);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(
        array $content, $gatewayPayment): array
    {
        $attributes = [];

        if ($this->shouldStatusBeUpdated($gatewayPayment) === true)
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseFields::RESPONSE_CODE];
        }

        if (isset($content[ResponseFields::BANK_PAYMENT_ID]) === true)
        {
            if (empty($gatewayPayment[Base\Entity::BANK_PAYMENT_ID]) === true)
            {
                $attributes[Base\Entity::BANK_PAYMENT_ID] = $content[ResponseFields::BANK_PAYMENT_ID];
            }
            else if ($gatewayPayment[Base\Entity::BANK_PAYMENT_ID] !==
                     $content[ResponseFields::BANK_PAYMENT_ID])
            {
                $this->trace->error(
                    TraceCode::GATEWAY_MULTIPLE_BANK_PAYMENT_IDS,
                    [
                        'authorize_bid' => $gatewayPayment[Base\Entity::BANK_PAYMENT_ID],
                        'verify_bid'    => $content[ResponseFields::BANK_PAYMENT_ID]
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

    protected function getMerchantId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->getLiveMerchantId();
        }

        return $this->getTestMerchantId();
    }

    protected function getLiveSecret()
    {
        if ($this->action === Action::AUTHORIZE)
        {
            return $this->input['terminal']['gateway_secure_secret'];
        }
        elseif ($this->action === Action::CALLBACK)
        {
            return $this->input['terminal']['gateway_secure_secret2'];
        }
        else
        {
            throw new LogicException(
                'Invalid action. Should not have reached here.',
                null,
                $this->action
            );
        }
    }

    protected function getTestSecret()
    {
        if ($this->action === Action::AUTHORIZE)
        {
            return $this->config['test_encryption_key'];
        }
        elseif ($this->action === Action::CALLBACK)
        {
            return $this->config['test_decryption_key'];
        }
        else
        {
            throw new LogicException(
                'Invalid action. Should not have reached here.',
                null,
                $this->action
            );
        }
    }

    public function getSalt()
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config['test_salt'];
        }
        elseif ($this->mode === Mode::LIVE)
        {
            return $this->config['live_salt'];
        }
        else
        {
            throw new LogicException(
                'Invalid mode. Should not have reached here.',
                null,
                $this->action
            );
        }
    }

    protected function getStringToHash($content, $glue = '|')
    {
        $hash_data = $this->getSalt();

        foreach ($content as $key => $value)
        {
            if (strlen($value) > 0)
            {
                $hash_data .= '|' . $value;
            }
        }

        return $hash_data;
    }

    protected function getHashOfString($str)
    {
        $secure_hash = null;

        $secure_hash = strtoupper(hash('sha512', $str));

        return $secure_hash;
    }
}
