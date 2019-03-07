<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Enach\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;

class Gateway extends Base\Gateway
{
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
                    'decrypted checksum'    => $decryptedChecksum,
                    'mandate response data' => $xmlData,
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
                    'mandate response data' => $xmlData,
                ]);

            $attributes = $this->getErrorResponseGatewayAttributes($xmlData);
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $attributes, false);

        $recurringData = $this->getRecurringDataFromNpciResponse($gatewayPayment);

        /**
         * Throwing an exception here for now. This only updates the payment entity to failed
         * The token related values - recurring status etc will be null
         **/

        if ($recurringData[Token\Entity::RECURRING_STATUS] === Token\RecurringStatus::REJECTED)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
                null,
                null,
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

        if (in_array($bank, Payment\Processor\Netbanking::$inconsistentIfsc) === true)
        {
            $bank = array_search ($bank, Payment\Processor\Netbanking::$defaultInconsistentBankCodesMapping);
        }

        $content = [
            RequestFields::MERCHANT_ID => $mid,
            RequestFields::REQUEST_XML => $signedxml,
            RequestFields::CHECKSUM    => $encryptedChecksum,
            RequestFields::BANK_ID     => $bank,
        ];

        $request = $this->getStandardRequestArray($content, 'post', 'npciauth');

        $request = $this->addHeadersForNpciRequest($request);

        $dataToTrace = [
            RequestFields::MERCHANT_ID => $mid,
            RequestFields::REQUEST_XML => $xml,
            RequestFields::CHECKSUM    => $encryptedChecksum,
            RequestFields::BANK_ID     => $bank,
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
            RequestNpciTags::MAX_AMOUNT            => $input['token']->getMaxAmount() / 100,
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

        $creditorAccount = $this->getCreditorAccount();

        $sponserIfsc = $this->getSponsorIfsc();

        $catCode = Base\CategoryCode::getCategoryCodeFromMcc($mcc);

        $currentDate = Carbon::now()->setTimezone(Timezone::IST)->format('Y-m-d\TH:i:s');

        $data = [
            NpciXmlHeaderTags::GROUP_HEADER      => [
                RequestNpciTags::MESSAGE_ID            => $pid,
                RequestNpciTags::CREATION_DATE_TIME    => $currentDate,
            ],

            NpciXmlHeaderTags::INFO              => [
                RequestNpciTags::MID                   => $mid,
                RequestNpciTags::CATEGORY_CODE         => $catCode,
                RequestNpciTags::UTILITY_CODE          => $mid,
                RequestNpciTags::CATEGORY_DESCRIPTION  => Base\CategoryCode::getCategoryDescriptionFromCode($catCode),
                RequestNpciTags::NAME                  => 'Razorpay software pvt ltd',
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
                RequestNpciTags::DEBTOR_NAME           => $input['token']->getBeneficiaryName(),
                RequestNpciTags::DEBTOR_ACCOUNT        => $encryptedData[RequestNpciTags::DEBTOR_ACCOUNT],
            ],

            NpciXmlHeaderTags::CREDITOR            => [
                RequestNpciTags::CREDITOR_NAME         => 'Razorpay software pvt ltd',
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
            $attr[Base\Entity::STATUS]               = RegistrationStatus::SUCCESS;
            $attr[Base\Entity::GATEWAY_REFERENCE_ID] = $data[ResponseXmlTags::ACCEPT_REF_NO]; // TODO is there a better field
        }
        else
        {
            $attr[Base\Entity::STATUS]              = RegistrationStatus::FAILURE;
            $attr[Base\Entity::ERROR_CODE]          = $data[ResponseXmlTags::REJECTION_CODE];
            $attr[Base\Entity::ERROR_MESSAGE]       = $data[ResponseXmlTags::REJECT_DESCRIPTION];
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

    protected function getRecurringDataFromNpciResponse($gatewayPayment)
    {
        $status = $gatewayPayment->getStatus();

        if (isset(RegistrationStatus::STATUS_TO_RECURRING_STATUS_MAP[$status]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['gateway_payment' => $gatewayPayment->toArray()]);
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

    // -------------------------- general helper functions ----------------------------------

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
