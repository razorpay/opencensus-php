<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use View;
use Crypt;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Bank\IFSC;
use RZP\Http\CheckoutView;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Constants\Environment;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Enach\Base\CategoryCode;
use RZP\Models\Locale\Core as LocaleCore;
use RZP\Models\Feature\Constants as Feature;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'enach_npci_netbanking';

    protected $crypto;

    protected $changeBankCodeGatewayMapping = [
        IFSC::UJVN => 'USFB',
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            $this->authorizeSecondRecurring($input);

            return null;
        }

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

        $responseXml = (array) simplexml_load_string(trim($responseXmlString));

        $json = json_encode($responseXml);

        $responseArray = json_decode($json, true);

        $signPresent = $this->crypto->checkSignaturePresent($responseXmlString);

        if(($input['gateway'][ResponseFields::RESPONSE_TYPE] === ResponseType::SUCCESS) or ($signPresent !== null))
        {
            $this->crypto->verifySignature($responseXmlString, $this->crypto->getEncryptionPublicKey());
        }

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

            $exceptionData = [
                'emandate_details' => self::fetchEmandateDisplayDetails(
                    $input['payment'],
                    $input['token'],
                    $input['terminal'],
                    $input['merchant'],
                    $this->config,
                    $this->mode
                )
            ];

            throw new Exception\GatewayErrorException(
                $errorCode,
                $gatewayPayment->getErrorCode(),
                $gatewayPayment->getErrorMessage(),
                $exceptionData
            );
        }

        return $this->getCallbackResponseData($input, $recurringData);
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

        $mid2 = $this->getMerchantId2();

        $bank = $input['payment']['bank'];

        $authType = AuthType::getAuthType($input);

        if (in_array($bank, Payment\Processor\Netbanking::$inconsistentIfsc) === true)
        {
            $bank = array_search ($bank, Payment\Processor\Netbanking::$defaultInconsistentBankCodesMapping);
        }

        // Change bank code if ISFC present in changeBankCodeGatewayMapping
        $bank = $this->getBankCodeMapping($bank);

        $content = [
            RequestFields::MERCHANT_ID => $mid2,
            RequestFields::REQUEST_XML => $signedxml,
            RequestFields::CHECKSUM    => $encryptedChecksum,
            RequestFields::BANK_ID     => $bank,
            RequestFields::AUTH_MODE   => $authType,
            RequestFields::SPID        => $mid2 . '_22',
        ];

        $request = $this->getStandardRequestArray($content, 'post', 'npciauth_old');

        $request = $this->addHeadersForNpciRequest($request);

        $dataToTrace = [
            RequestFields::MERCHANT_ID => $mid2,
            RequestFields::REQUEST_XML => $xml,
            RequestFields::CHECKSUM    => $encryptedChecksum,
            RequestFields::BANK_ID     => $bank,
            RequestFields::AUTH_MODE   => $authType,
            RequestFields::SPID        => $mid2 . '_22',
        ];

        $this->traceGatewayPaymentRequest($dataToTrace, $input);

        $request['method'] = 'direct';

        $request['content'] = $this->getRequestContentAsView($request, $input);

        return $request;
    }

    protected function getSecureData($input)
    {
        $tokenExpiry = $input['token']->getExpiredAt();

        $date = Carbon::createFromTimestamp($input['payment'][Payment\Entity::CREATED_AT], Timezone::IST)
                        ->format('Y-m-d+05:30');

        if ($tokenExpiry === null)
        {
            $finalCollection = '';
        }
        else
        {
            $finalCollection = Carbon::now(Timezone::IST)->setTimestamp($tokenExpiry)->format('Y-m-d+05:30');
        }

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

        if ($secureData[RequestNpciTags::FINAL_COLLECTION_DATE] === '')
        {
            unset($secureData[RequestNpciTags::FINAL_COLLECTION_DATE]);
        }

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

        $mid2 = $this->getMerchantId2();

        $pid = $input['payment']['id'];

        $mcc = $input['terminal']['category'];

        $merchantName = $this->getMerchantName($input['terminal'], $input['merchant']);

        $creditorAccount = $this->getCreditorAccount();

        $sponserIfsc = $this->getSponsorIfsc();

        $sponserBank = $this->getSponsorBank();

        $catCode = Base\CategoryCode::getCategoryCodeFromMcc($mcc);

        $accountType = isset($input['token']['account_type']) ? strtoupper($input['token']['account_type']) : 'SAVINGS';

        $createdDate = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST)
                              ->format('Y-m-d\TH:i:s');

        $data = [
            NpciXmlHeaderTags::GROUP_HEADER      => [
                RequestNpciTags::MESSAGE_ID            => $pid,
                RequestNpciTags::CREATION_DATE_TIME    => $createdDate,
            ],

            NpciXmlHeaderTags::INFO              => [
                RequestNpciTags::MID                   => $mid2,
                RequestNpciTags::CATEGORY_CODE         => $catCode,
                RequestNpciTags::UTILITY_CODE          => $mid2,
                RequestNpciTags::CATEGORY_DESCRIPTION  => str_limit(Base\CategoryCode::getCategoryDescriptionFromCode($catCode), 25, ''),
                RequestNpciTags::NAME                  => $merchantName,
                RequestNpciTags::SPONSORED_BANK_NAME   => $sponserBank
            ],

            NpciXmlHeaderTags::MANDATE           => [
                RequestNpciTags::MANDATE_ID           => $pid,
                RequestNpciTags::MANDATE_TYPE         => 'DEBIT',
            ],

            NpciXmlHeaderTags::OCCURENCE          => [
                RequestNpciTags::SEQUENCE_TYPE         => 'RCUR',
                RequestNpciTags::FREQUENCY             => Frequency::ADHOC,
                RequestNpciTags::FIRST_COLLECTION_DATE => $encryptedData[RequestNpciTags::FIRST_COLLECTION_DATE],
                RequestNpciTags::FINAL_COLLECTION_DATE => $encryptedData[RequestNpciTags::FINAL_COLLECTION_DATE] ?? null,
            ],

            RequestNpciTags::MAX_AMOUNT            => $encryptedData[RequestNpciTags::MAX_AMOUNT],

            NpciXmlHeaderTags::DEBTOR              => [
                RequestNpciTags::DEBTOR_NAME           => str_limit($input['token']->getBeneficiaryName(), 40, ''),
                RequestNpciTags::DEBTOR_ACCOUNT        => $encryptedData[RequestNpciTags::DEBTOR_ACCOUNT],
                RequestNpciTags::ACCOUNT_TYPE          => $accountType
            ],

            NpciXmlHeaderTags::CREDITOR            => [
                RequestNpciTags::CREDITOR_NAME         => $merchantName,
                RequestNpciTags::CREDITOR_ACCOUNT      => $creditorAccount,
                RequestNpciTags::IFSC_SPONSOR          => $sponserIfsc,
            ]
        ];

        if ($data[NpciXmlHeaderTags::OCCURENCE][RequestNpciTags::FINAL_COLLECTION_DATE] === null)
        {
            unset($data[NpciXmlHeaderTags::OCCURENCE][RequestNpciTags::FINAL_COLLECTION_DATE]);
        }

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

        $this->addChildren($data[NpciXmlHeaderTags::MANDATE], $mandate);

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

    public function getMerchantId2()
    {
        $mid2 = $this->getLiveMerchantId2();

        if ($this->mode === Mode::TEST)
        {
            $mid2 = $this->getTestMerchantId2();
        }

        return $mid2;
    }

    protected function getCreditorAccount()
    {
        if ($this->mode === Mode::TEST)
        {
            $credAccount = $this->config['test_emandate_npci_creditor_account'];
        }
        else
        {
            $credAccount = $this->getLiveMerchantId2();
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

    protected function getSponsorBank()
    {
        if ($this->mode === Mode::TEST)
        {
            $sponsor = $this->config['test_emandate_npci_sponser_bank'];
        }
        else
        {
            $sponsor = $this->getLiveGatewayTerminalId();
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

    protected function getBankCodeMapping($bank)
    {
        if (array_key_exists($bank, $this->changeBankCodeGatewayMapping) === true)
        {
            $bank = $this->changeBankCodeGatewayMapping[$bank];
        }

        return $bank;
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

            ResponseXmlTags::MANDATE_ID         => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::ACCEPT_DETAILS]
                                                                 [ResponseXmlTags::ORIGINAL_MSG_INFO]
                                                                 [ResponseXmlTags::MANDATE_ID] ?? null,

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
            $attr[Base\Entity::UMRN]                  = $data[ResponseXmlTags::MANDATE_ID];
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
            Token\Entity::GATEWAY_TOKEN            => $gatewayPayment->getUmrn(),
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'    => $this->gateway,
                'request'    => $request,
                'payment_id' => $verify->input['payment']['id'],
            ]
        );

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
            RequestFields::MERCHANT_ID   => $this->getMerchantId2(),
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

    /**
     * @throws Exception\GatewayErrorException
     */
    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseXmlTags::REJECTION_CODE]) === true) and
            ($content[ResponseXmlTags::REJECTION_CODE] === RegistrationStatus::VERIFY_SUCCESS))
        {
            if (empty($content[ResponseXmlTags::MANDATE_ID]) === true)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                    null,
                    null,
                    [
                        'reason' => 'success response should have gateway token present, but is empty'
                    ]
                );
            }

            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyResponse(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $attributes = $this->getVerifyAttributesToSave($verify);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getVerifyAttributesToSave(Verify $verify): array
    {
        $content = $verify->verifyResponseContent;

        $gatewayPayment = $verify->payment;

        $attributes = [];

        if ((isset($content[ResponseXmlTags::ACCEPTED]) === true) and
            (($content[ResponseXmlTags::ACCEPTED] === 'true') or ($content[ResponseXmlTags::ACCEPTED] === 'false')))
        {
            $attributes[Base\Entity::GATEWAY_REFERENCE_ID]  = $content[ResponseXmlTags::VER_NPCI_REF_ID];
            $attributes[Base\Entity::GATEWAY_REFERENCE_ID2] = $content[ResponseXmlTags::ACCEPT_REF_NO];
            $attributes[Base\Entity::UMRN]                  = $content[ResponseXmlTags::MANDATE_ID] ?? null;
        }

        if((empty($content[ResponseXmlTags::REJECTION_CODE]) === true) or
            ($content[ResponseXmlTags::REJECTION_CODE] === 'NULL'))
        {
            $attributes[Base\Entity::ERROR_CODE]            = $content[ResponseXmlTags::ERROR_CODE] ?? null;
            $attributes[Base\Entity::ERROR_MESSAGE]         = $content[ResponseXmlTags::ERROR_DESCRIPTION] ?? null;
        }
        else
        {
            $attributes[Base\Entity::ERROR_CODE]            = $content[ResponseXmlTags::REJECTION_CODE] ?? null;
            $attributes[Base\Entity::ERROR_MESSAGE]         = $content[ResponseXmlTags::REJECT_DESCRIPTION] ?? null;
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


    protected function getRequestContentAsView($request, $input)
    {
        if ($this->mock === true)
        {
            $request['url'] =  $this->route->getUrlWithPublicAuth(
                'mock_emandate_payment',
                ['authType' => 'netbanking']
            );
        }

        $languageCode = LocaleCore::setLocale($input, $input['merchant']->getId());

        $postFormData['type'] = 'first';

        $postFormData['request'] = $request;

        $postFormData['version'] = 1;

        $postFormData['payment_id'] = $input['payment']['public_id'];

        $postFormData['gateway'] = Crypt::encrypt($input['payment']['gateway'] . '__' . time());

        $postFormData['amount'] = '0.00';

        $postFormData['image'] = $input['merchant']->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE);

        $postFormData['theme']['color'] = $input['merchant']->getBrandColorElseDefault();

        $postFormData['name'] = $input['merchant']->getBillingLabel();

        $postFormData['nobranding'] = $input['merchant']->isFeatureEnabled(Feature::PAYMENT_NOBRANDING);

        $postFormData['production'] = $this->app->environment() === Environment::PRODUCTION;

        $postFormData['merchant_id'] = $input['merchant']->getId();

        $postFormData['language_code'] = $languageCode;

        $postFormData['emandate_details'] = self::fetchEmandateDisplayDetails(
                                                                              $input['payment'],
                                                                              $input['token'],
                                                                              $input['terminal'],
                                                                              $input['merchant'],
                                                                              $this->config,
                                                                              $this->mode
                                                                              );

        $merchant = $input['merchant'];

        $postFormData += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return View::make('gateway.gatewayNachNbForm')->with('data', $postFormData)->render();
    }

    public static function fetchEmandateDisplayDetails(
                                                       $payment,
                                                       $token,
                                                       $terminal,
                                                       $merchant,
                                                       $config,
                                                       $mode = Mode::TEST
                                                      )
    {
        $bank   = $payment['bank'];
        $expiry = $token->getExpiredAt();

        if (in_array($bank, Payment\Processor\Netbanking::$inconsistentIfsc) === true)
        {
            $bank = array_search ($bank, Payment\Processor\Netbanking::$defaultInconsistentBankCodesMapping);
        }

        if (($terminal->getMerchantId() === Merchant\Account::DEMO_ACCOUNT) or ($mode === Mode::TEST))
        {
            $merchantName =  'Razorpay Software Pvt Ltd';
        }
        else
        {
            $merchantName = $merchant->getFilteredDba();
        }

        $startDate = Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('d/m/Y');
        $endDate   = Carbon::now(Timezone::IST)->setTimestamp($expiry)->format('d/m/Y');

        if ($expiry === null)
        {
            $endDate = 'Valid Until Cancelled';
        }

        if ($mode === Mode::TEST)
        {
            $utilityCode = $config['test_merchant_id'];
        }
        else
        {
            $utilityCode = $terminal['gateway_merchant_id2'];
        }

        $categoryDescription = str_limit(
                                         CategoryCode::getCategoryDescriptionFromCode
                                                (
                                                    CategoryCode::getCategoryCodeFromMcc($terminal['category'])
                                                ),
                                   25,
                                   '');
    
        $merchantName = CategoryCode::getCorporateName($utilityCode) ?? $merchantName;

        $displayDetails = [
            'customer_name'      => str_limit($token->getBeneficiaryName(), 40, ''),
            'bank'               => $bank,
            'account_number'     => mask_except_last4($token->getAccountNumber()),
            'max_amount'         => number_format($token->getMaxAmount() / 100, 2, '.', ''),
            'debit_type'         => 'Max Amount',
            'mandate_start_date' => $startDate,
            'mandate_end_date'   => $endDate,
            'frequency'          => 'As And When Presented',
            'corporate_name'     => str_limit($merchantName, 25, ''),
            'utility_code'       => $utilityCode,
            'purpose_text'       => $categoryDescription,
            'reference_number'   => $token->getGatewayToken(),
        ];

        return $displayDetails;
    }

    /**
     * @throws Exception\BadRequestException
     */
    protected function authorizeSecondRecurring(array $input)
    {
        if (empty($input['token']->getGatewayToken()) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TOKEN_EMPTY,
                Token\Entity::GATEWAY_TOKEN,
                [
                    'payment' => $input['payment'],
                    'token'   => $input['token']->toArray(),
                ]);
        }

        $entity = [
            Base\Entity::AMOUNT => $input['payment'][Payment\Entity::AMOUNT] / 100,
            Base\Entity::UMRN   => $input['token']->getGatewayToken(),
        ];

        $this->createGatewayPaymentEntity($entity, null);
    }
}
