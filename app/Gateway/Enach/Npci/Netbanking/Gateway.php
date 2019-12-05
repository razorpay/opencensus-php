<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'enach_npci_netbanking';

    protected $crypto;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $this->setCrypto();

        $this->createGatewayPaymentEntity([], null, 'authorize');

        $request = $this->getAuthRequest($input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->setCrypto();

        $responseXmlString = $input['gateway'][ResponseFields::RESPONSE_XML];

        $this->crypto->verifySignature($responseXmlString, $this->crypto->getEncryptionPublicKey());

        $responseXml = (array) simplexml_load_string(trim($responseXmlString));

        $json = json_encode($responseXml);

        $responseArray = json_decode($json, true);

        if ($input['gateway'][ResponseFields::RESPONSE_TYPE] === ResponseType::SUCCESS)
        {
            $xmlData = $this->getDataFromResponse($responseArray);

            $secureData = [
                $xmlData[ResponseXmlTags::ACCEPTED],
                $xmlData[ResponseXmlTags::ACCEPT_REF_NO],
                $xmlData[ResponseXmlTags::REJECTION_CODE],
                $xmlData[ResponseXmlTags::REJECT_DESCRIPTION],
                $xmlData[ResponseXmlTags::REJECTION_BY]
            ];

            $decryptedChecksum = $this->crypto->decrypt($input['gateway'][ResponseFields::CHECKSUM]);

            $this->trace->info(
                TraceCode::GATEWAY_MANDATE_RESPONSE,
                [
                    'payment_id'            => $input['payment']['id'],
                    'gateway'               => $this->gateway,
                    'decrypted_checksum'    => $decryptedChecksum,
                    'mandate_response_data' => $xmlData,
                ]);

            $this->validateCallbackChecksum(
                $this->generateHash($secureData),
                $decryptedChecksum,
                $input['payment']['id']
            );

            $attributes = $this->getResponseGatewayAttributes($xmlData);
        }
        else
        {
            $xmlData = $this->getDataFromErrorResponse($responseArray);

            $this->trace->info(
                TraceCode::GATEWAY_MANDATE_RESPONSE,
                [
                    'payment_id'            => $input['payment']['id'],
                    'gateway'               => $this->gateway,
                    'mandate_response_data' => $xmlData,
                ]);

            $attributes = $this->getErrorResponseGatewayAttributes($xmlData);
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $attributes, false);

        $recurringData = $this->getRecurringData($gatewayPayment);

        /**
         * Throwing an exception here for now. This only updates the payment entity to failed
         * The token related values - recurring status etc will be null
         **/

        if ($recurringData[Token\Entity::RECURRING_STATUS] === Token\RecurringStatus::REJECTED)
        {
            $errorCode = ErrorCodes\NetbankingErrorCodes::getInternalErrorCode($gatewayPayment->getErrorCode());

            throw new Exception\GatewayErrorException(
                $errorCode,
                $gatewayPayment->getErrorCode(),
                $gatewayPayment->getErrorMessage(),
                $recurringData
            );
        }

        return $this->getCallbackResponseData($input, $recurringData);
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return back.
        if ($gatewayPayment->getStatus() === RegistrationStatus::SUCCESS)
        {
            return true;
        }

        $attributes = [
            Base\Entity::STATUS          => RegistrationStatus::SUCCESS,
            Base\Entity::ERROR_MESSAGE   => null,
            Base\Entity::ERROR_CODE      => null
        ];

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    // -------------------------- authorize helper functions ----------------------------------

    protected function setCrypto()
    {
        $this->crypto = new Crypto($this->config, $this->mode);
    }

    protected function getAuthRequest($input)
    {
        $secureData = $this->getSecureData($input);

        $checksum = $this->generateHash($secureData);

        $encryptedChecksum = $this->crypto->encrypt($checksum);

        $data = $this->getDataForXml($input, $secureData);

        $xml = $this->getXml($data);

        $signedxml = $this->crypto->addSignature($xml);

        $mid = $this->getMerchantId();

        $bank = $input['payment']['bank'];

        $authType = AuthType::getAuthType($input);

        if (in_array($bank, Payment\Processor\Netbanking::$inconsistentIfsc) === true)
        {
            $bank = array_search ($bank, Payment\Processor\Netbanking::$defaultInconsistentBankCodesMapping);
        }

        $content = [
            RequestFields::MERCHANT_ID => $mid,
            RequestFields::REQUEST_XML => $signedxml,
            RequestFields::CHECKSUM    => $encryptedChecksum,
            RequestFields::BANK_ID     => $bank,
            RequestFields::AUTH_MODE   => $authType,
        ];

        $request = $this->getStandardRequestArray($content, 'post', 'npciauth');

        $request = $this->addHeadersForNpciRequest($request);

        $dataToTrace = [
            RequestFields::MERCHANT_ID => $mid,
            RequestFields::REQUEST_XML => $xml,
            RequestFields::CHECKSUM    => $encryptedChecksum,
            RequestFields::BANK_ID     => $bank,
            RequestFields::AUTH_MODE   => $authType
        ];

        $this->traceGatewayPaymentRequest($dataToTrace, $input);

        return $request;
    }

    protected function getSecureData($input)
    {
        $date = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT], Timezone::IST)
                        ->format('Y-m-d+05:30');

        $finalCollection = Carbon::createFromTimestamp($input['token']->getExpiredAt(), Timezone::IST)
                                   ->format('Y-m-d+05:30');

        return [
            RequestNpciTags::DEBTOR_ACCOUNT        => $input['token']->getAccountNumber(),
            RequestNpciTags::FIRST_COLLECTION_DATE => $date,
            RequestNpciTags::FINAL_COLLECTION_DATE => $finalCollection,
            RequestNpciTags::COLLECTION_AMOUNT     => '',
            RequestNpciTags::MAX_AMOUNT            => number_format($input['token']->getMaxAmount() / 100, 2, '.', ''),
        ];
    }

    protected function getEncryptedData($secureData)
    {
        unset($secureData[RequestNpciTags::COLLECTION_AMOUNT]);

        $encryptedData = [];

        foreach ($secureData as $key => $value)
        {
            $encrypted = $this->crypto->encrypt($value);

            $encryptedData[$key] = $encrypted;
        }
        return $encryptedData;
    }

    protected function getDataForXml($input, $secureData)
    {
        $encryptedData = $this->getEncryptedData($secureData);

        $mid = $this->getMerchantId();

        $pid = $input['payment']['id'];

        $mcc = $input['terminal']['category'];

        $merchantName = $this->getMerchantName($input['terminal'], $input['merchant']);

        $creditorAccount = $this->getCreditorAccount();

        $sponserIfsc = $this->getSponsorIfsc();

        $catCode = Base\CategoryCode::getCategoryCodeFromMcc($mcc);

        $createdDate = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                              ->format('Y-m-d\TH:i:s');

        $data = [
            NpciXmlHeaderTags::GROUP_HEADER      => [
                RequestNpciTags::MESSAGE_ID            => $pid,
                RequestNpciTags::CREATION_DATE_TIME    => $createdDate,
            ],

            NpciXmlHeaderTags::INFO              => [
                RequestNpciTags::MID                   => $mid,
                RequestNpciTags::CATEGORY_CODE         => $catCode,
                RequestNpciTags::UTILITY_CODE          => $mid,
                RequestNpciTags::CATEGORY_DESCRIPTION  => str_limit(Base\CategoryCode::getCategoryDescriptionFromCode($catCode), 25, ''),
                RequestNpciTags::NAME                  => $merchantName,
            ],

            RequestNpciTags::MANDATE_ID           => $pid,

            NpciXmlHeaderTags::OCCURENCE          => [
                RequestNpciTags::SEQUENCE_TYPE         => 'RCUR',
                RequestNpciTags::FREQUENCY             => Frequency::ADHOC,
                RequestNpciTags::FIRST_COLLECTION_DATE => $encryptedData[RequestNpciTags::FIRST_COLLECTION_DATE],
                RequestNpciTags::FINAL_COLLECTION_DATE => $encryptedData[RequestNpciTags::FINAL_COLLECTION_DATE],
            ],

            RequestNpciTags::MAX_AMOUNT            => $encryptedData[RequestNpciTags::MAX_AMOUNT],

            NpciXmlHeaderTags::DEBTOR              => [
                RequestNpciTags::DEBTOR_NAME           => str_limit($input['token']->getBeneficiaryName(), 40, ''),
                RequestNpciTags::DEBTOR_ACCOUNT        => $encryptedData[RequestNpciTags::DEBTOR_ACCOUNT],
            ],

            NpciXmlHeaderTags::CREDITOR            => [
                RequestNpciTags::CREDITOR_NAME         => $merchantName,
                RequestNpciTags::CREDITOR_ACCOUNT      => $creditorAccount,
                RequestNpciTags::IFSC_SPONSOR          => $sponserIfsc,
            ]
        ];

        return $data;
    }

    protected function getXml($data)
    {
        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            .'<Document xmlns="http://npci.org/onmags/schema"/>');

        $mandateroot = $document->addChild(NpciXmlHeaderTags::MANDATE_ROOT_HEADER);

        $grp = $mandateroot->addChild(NpciXmlHeaderTags::GROUP_HEADER);

        $this->addChildren($data[NpciXmlHeaderTags::GROUP_HEADER], $grp);

        $req = $grp->addChild(NpciXmlHeaderTags::REQUEST_INITIATING_PARTY);

        $info = $req->addChild(NpciXmlHeaderTags::INFO);

        $this->addChildren($data[NpciXmlHeaderTags::INFO], $info);

        $mandate = $mandateroot->addChild(NpciXmlHeaderTags::MANDATE);

        $mandate->addChild(RequestNpciTags::MANDATE_ID, $data[RequestNpciTags::MANDATE_ID]);

        $occurrence = $mandate->addChild(NpciXmlHeaderTags::OCCURENCE);

        $this->addChildren($data[NpciXmlHeaderTags::OCCURENCE], $occurrence);

        $maxAmount = $mandate->addChild(RequestNpciTags::MAX_AMOUNT, $data[RequestNpciTags::MAX_AMOUNT]);

        $maxAmount->addAttribute('Ccy', 'INR');

        $debtor = $mandate->addChild(NpciXmlHeaderTags::DEBTOR);

        $this->addChildren($data[NpciXmlHeaderTags::DEBTOR], $debtor);

        $creditor = $mandate->addChild(NpciXmlHeaderTags::CREDITOR);

        $this->addChildren($data[NpciXmlHeaderTags::CREDITOR], $creditor);

        $xmlString = $document->asXml();

        $xmlString = str_replace("\n", '', $xmlString); // remove new lines
        $xmlString = str_replace("\r", '', $xmlString); // remove carraige return
        $xmlString = preg_replace('/\s\s+/', '', $xmlString); // remove consecutive spaces

        return $xmlString;
    }

    protected function addHeadersForNpciRequest($request)
    {
        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $request['headers'] = $headers;

        return $request;
    }

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function getCreditorAccount()
    {
        if ($this->mode === Mode::TEST)
        {
            $credAccount = $this->config['test_emandate_npci_creditor_account'];
        }
        else
        {
            $credAccount = $this->getLiveMerchantId();
        }

        return $credAccount;
    }

    protected function getSponsorIfsc()
    {
        if ($this->mode === Mode::TEST)
        {
            $sponsor = $this->config['test_emandate_npci_sponser_ifsc'];
        }
        else
        {
            $sponsor = $this->getLiveGatewayAccessCode();
        }

        return $sponsor;
    }

    protected function getMerchantName($terminal, $merchant)
    {
        if (($this->isShared($terminal)) or ($this->isTestMode() === true))
        {
            $merchantName =  'Razorpay software pvt ltd';
        }
        else
        {
            $merchantName = $merchant->getFilteredDba();
        }

        return str_limit($merchantName, 25, '');
    }

    // -------------------------- callback helper functions ----------------------------------

    protected function getDataFromResponse($responseArray)
    {
        $data = [
            ResponseXmlTags::MESSAGE_ID         => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::GROUP_HEADER]
                                                                 [ResponseXmlTags::MESSAGE_ID],

            ResponseXmlTags::CREATION_DATE_TIME => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::GROUP_HEADER]
                                                                 [ResponseXmlTags::CREATION_DATE_TIME],

            ResponseXmlTags::RESPONSE_PARTY     => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::GROUP_HEADER]
                                                                 [ResponseXmlTags::RESPONSE_PARTY],

            ResponseXmlTags::MANDATE_REQUEST_ID => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::ACCEPT_DETAILS]
                                                                 [ResponseXmlTags::ORIGINAL_MSG_INFO]
                                                                 [ResponseXmlTags::MANDATE_REQUEST_ID],

            ResponseXmlTags::ORIGINGAL_MSG_ID   => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::ACCEPT_DETAILS]
                                                                 [ResponseXmlTags::ORIGINAL_MSG_INFO]
                                                                 [ResponseXmlTags::ORIGINGAL_MSG_ID],

            ResponseXmlTags::REQUEST_DATE_TIME  => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::ACCEPT_DETAILS]
                                                                 [ResponseXmlTags::ORIGINAL_MSG_INFO]
                                                                 [ResponseXmlTags::MANDATE_REQUEST_CREATION_DATE_TIME],

            ResponseXmlTags::ACCEPTED           => $this->crypto->decrypt(
                                                    $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                  [ResponseXmlTags::ACCEPT_DETAILS]
                                                                  [ResponseXmlTags::ACCEPT_RESULT]
                                                                  [ResponseXmlTags::ACCEPTED]),

            ResponseXmlTags::ACCEPT_REF_NO      => $this->crypto->decrypt(
                                                    $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                  [ResponseXmlTags::ACCEPT_DETAILS]
                                                                  [ResponseXmlTags::ACCEPT_RESULT]
                                                                  [ResponseXmlTags::ACCEPT_REF_NO]),

            ResponseXmlTags::REJECTION_CODE     => $this->crypto->decrypt(
                                                    $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                  [ResponseXmlTags::ACCEPT_DETAILS]
                                                                  [ResponseXmlTags::ACCEPT_RESULT]
                                                                  [ResponseXmlTags::REJECT_REASON]
                                                                  [ResponseXmlTags::REJECTION_CODE]),

            ResponseXmlTags::REJECT_DESCRIPTION => $this->crypto->decrypt(
                                                    $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                  [ResponseXmlTags::ACCEPT_DETAILS]
                                                                  [ResponseXmlTags::ACCEPT_RESULT]
                                                                  [ResponseXmlTags::REJECT_REASON]
                                                                  [ResponseXmlTags::REJECT_DESCRIPTION]),

            ResponseXmlTags::REJECTION_BY       => $this->crypto->decrypt(
                                                    $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                  [ResponseXmlTags::ACCEPT_DETAILS]
                                                                  [ResponseXmlTags::ACCEPT_RESULT]
                                                                  [ResponseXmlTags::REJECT_REASON]
                                                                  [ResponseXmlTags::REJECTION_BY]),
        ];

        foreach ($data as $key => $value)
        {
            if (empty($data[$key]) === true)
            {
                $data[$key] = '';
            }
        }

        return $data;
    }

    protected function getDataFromErrorResponse($responseArray)
    {
        return [
            ResponseXmlTags::MESSAGE_ID         => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::GROUP_HEADER]
                                                                 [ResponseXmlTags::MESSAGE_ID],

            ResponseXmlTags::CREATION_DATE_TIME => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::GROUP_HEADER]
                                                                 [ResponseXmlTags::CREATION_DATE_TIME],

            ResponseXmlTags::RESPONSE_PARTY     => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::GROUP_HEADER]
                                                                 [ResponseXmlTags::RESPONSE_PARTY],

            ResponseXmlTags::MANDATE_REQUEST_ID => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::ORIGINIAL_REQUEST_INFO]
                                                                 [ResponseXmlTags::MANDATE_REQUEST_ID],

            ResponseXmlTags::ORIGINGAL_MSG_ID   => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::ORIGINIAL_REQUEST_INFO]
                                                                 [ResponseXmlTags::ORIGINGAL_MSG_ID],

            'Mandate_Creation_Date_Time'        => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::ORIGINIAL_REQUEST_INFO]
                                                                 [ResponseXmlTags::MANDATE_REQUEST_CREATION_DATE_TIME],

            ResponseXmlTags::ERROR_CODE         => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::MANDATE_ERROR_DETAILS]
                                                                 [ResponseXmlTags::ERROR_CODE],

            ResponseXmlTags::ERROR_DESCRIPTION  => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::MANDATE_ERROR_DETAILS]
                                                                 [ResponseXmlTags::ERROR_DESCRIPTION],

            ResponseXmlTags::REJECTION_BY       => $responseArray[ResponseXmlTags::MANDATE_REJECT_RESPONSE]
                                                                 [ResponseXmlTags::MANDATE_ERROR_DETAILS]
                                                                 [ResponseXmlTags::REJECTION_BY],
        ];
    }

    protected function getResponseGatewayAttributes($data)
    {
        $attr = [];

        $accepted = $data[ResponseXmlTags::ACCEPTED];

        if ($accepted === RegistrationStatus::SUCCESS)
        {
            $attr[Base\Entity::STATUS]                = RegistrationStatus::SUCCESS;
            $attr[Base\Entity::GATEWAY_REFERENCE_ID]  = $data[ResponseXmlTags::ORIGINGAL_MSG_ID];
            $attr[Base\Entity::GATEWAY_REFERENCE_ID2] = $data[ResponseXmlTags::ACCEPT_REF_NO];
        }
        else
        {
            $attr[Base\Entity::STATUS]               = RegistrationStatus::FAILURE;
            $attr[Base\Entity::GATEWAY_REFERENCE_ID] = $data[ResponseXmlTags::ORIGINGAL_MSG_ID];
            $attr[Base\Entity::ERROR_CODE]           = $data[ResponseXmlTags::REJECTION_CODE];
            $attr[Base\Entity::ERROR_MESSAGE]        = $data[ResponseXmlTags::REJECT_DESCRIPTION];
        }

        $attr[Base\Entity::ACKNOWLEDGE_STATUS] = 'true';

        return $attr;
    }

    protected function getErrorResponseGatewayAttributes($data)
    {
        return [
            Base\Entity::STATUS        => RegistrationStatus::FAILURE,
            Base\Entity::ERROR_CODE    => $data[ResponseXmlTags::ERROR_CODE],
            Base\Entity::ERROR_MESSAGE => $data[ResponseXmlTags::ERROR_DESCRIPTION],
        ];
    }

    protected function getRecurringData($gatewayPayment)
    {
        $status = $gatewayPayment->getStatus();

        if (isset(RegistrationStatus::STATUS_TO_RECURRING_STATUS_MAP[$status]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                [
                    'expected' => array_keys(RegistrationStatus::STATUS_TO_RECURRING_STATUS_MAP),
                    'actual'   => $status,
                ]);
        }

        $recurringStatus = RegistrationStatus::STATUS_TO_RECURRING_STATUS_MAP[$status];

        $errorCode = $gatewayPayment->getErrorCode();

        $recurringFailureReason = null;

        if ($recurringStatus === Token\RecurringStatus::REJECTED)
        {
            $recurringFailureReason = ErrorCodes\NetbankingErrorCodes::getEmandateRegisterErrorDescriptionFromCode($errorCode);
        }

        $recurringData = [
            Token\Entity::RECURRING_STATUS         => $recurringStatus,
            Token\Entity::RECURRING_FAILURE_REASON => $recurringFailureReason,
            Token\Entity::ACKNOWLEDGED_AT          => Carbon::now(Timezone::IST)->getTimestamp()
        ];

        return $recurringData;
    }

    protected function validateCallbackChecksum($calculated, $expected, $paymentId)
    {
        if ($calculated !== $expected)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification',
                '',
                [
                    'payment_id'            => $paymentId,
                    'gateway'               => $this->gateway,
                ]);
        }
    }

    // -------------------------- verify helper functions ----------------------------------

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        if (($verify->input['payment']['recurring_type'] === Payment\RecurringType::AUTO) and
            ($verify->input['payment']['recurring'] === true))
        {
            throw new Exception\PaymentVerificationException(
                [], $verify, Payment\Verify\Action::FINISH);
        }

        $request = $this->getVerifyRequest($verify);

        $response = $this->sendGatewayRequest($request);

        $decodedJson = json_decode($response->body, true);

        $verify->verifyResponseContent = $decodedJson[ResponseFields::TRANSACTION_STATUS][0];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'gateway'          => $this->gateway,
                'raw_response'     => $response->body,
                'decoded_response' => $verify->verifyResponseContent,
                'payment_id'       => $verify->input['payment']['id'],
            ]
        );
    }

    protected function getVerifyRequest(Verify $verify)
    {
        $input = $verify->input;

        $mandateReqBlock = [
            RequestFields::MERCHANT_ID   => $this->getMerchantId(),
            RequestFields::MANDATE_ID    => $input['payment']['id'],
            RequestFields::REQ_INIT_DATE => Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                                                    ->format('Y-m-d')
        ];

        $content = [
            RequestFields::MANDATE_REQ_ID_LIST  => [$mandateReqBlock]
        ];

        $request = $this->getStandardRequestArray(json_encode($content), 'post');

        if ($this->mode == Mode::TEST)
        {
            $request['options']['verify'] = false;
        }

        $request['headers']['Content-Type'] = 'application/json';

        return $request;
    }

    protected function verifyPayment(Verify $verify)
    {
        $status = $this->getVerifyMatchStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->payment = $this->saveVerifyResponse($verify);
    }

    protected function getVerifyMatchStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseXmlTags::ACCEPTED]) === true) and
            ($content[ResponseXmlTags::ACCEPTED] === RegistrationStatus::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyResponse(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributesToSave($verify);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        $gatewayRefId  = $gatewayPayment->getGatewayReferenceId();
        $gatewayRefId2 = $gatewayPayment->getGatewayReferenceId2();

        $attributes = [];

        if ((empty($gatewayRefId) === true) or (empty($gatewayRefId2) === true))
        {
            $attributes[Base\Entity::GATEWAY_REFERENCE_ID]  = $content[ResponseXmlTags::VER_NPCI_REF_ID];
            $attributes[Base\Entity::GATEWAY_REFERENCE_ID2] = $content[ResponseXmlTags::ACCEPT_REF_NO];
        }

        if ((isset($gatewayPayment[Base\Entity::STATUS]) === false) or
            ($verify->match === false))
        {
            $attributes[Base\Entity::STATUS] = $content[ResponseXmlTags::ACCEPTED] ?? RegistrationStatus::FAILURE;
        }

        return $attributes;
    }

    protected function extractPaymentsProperties($gatewayPayment)
    {
        $response = [];

        // For api based emandate initial payments, if late authorized,
        // we need to update the token status to confirmed
        if ($this->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $recurringData = $this->getRecurringData($gatewayPayment);

            $response = array_merge($response, $recurringData);
        }

        return $response;
    }

    // -------------------------- general helper functions ----------------------------------

    protected function isShared($terminal)
    {
        $merchant = $terminal->getMerchantId();

        return ($merchant === Merchant\Account::DEMO_ACCOUNT);
    }

    public function generateHash($content)
    {
        $hashString = $this->getStringToHash($content);

        return $this->getHashOfString($hashString);
    }

    protected function getStringToHash($content, $glue = '|')
    {
        return implode($glue, $content);
    }

    protected function getHashOfString($string)
    {
        return hash(HashAlgo::SHA256, $string);
    }

    private function addChildren($data, $xml)
    {
        foreach ($data as $key => $value)
        {
            $xml->addChild($key,$data[$key]);
        }
    }
}
