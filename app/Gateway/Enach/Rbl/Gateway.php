<?php

namespace RZP\Gateway\Enach\Rbl;

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
use RZP\Gateway\Enach\Base\Entity;
use RZP\Models\Settlement\Holidays;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Enach\Base\CategoryCode;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'enach_rbl';

    protected $crypto;

    /**
     * @param array $input
     * @return array|void
     * @throws Exception\GatewayErrorException
     */

    public function authorize(array $input)
    {
        parent::authorize($input);

        if (($input['payment']['method'] === Payment\Method::EMANDATE) and
            ($input['payment']['auth_type'] === Payment\AuthType::NETBANKING))
        {
            return $this->netbankingAuthorize($input);
        }

        $input['gateway'] = $this->getGatewayInput($input);

        $content = [
            Base\Entity::REGISTRATION_DATE => $input['gateway']['next_working_dt']->getTimestamp()
        ];

        try
        {
            $authenticationGateway = $input['authenticate']['gateway'];

            $authenticationResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $authenticationResponse['content']['reference_id'];

            $this->createGatewayPaymentEntity($content, $authenticationGateway, Action::AUTHORIZE);

            unset($authenticationResponse['content']['reference_id']);
        }

        catch (Exception\GatewayErrorException $e)
        {
            $responseArrary = $e->getData();

            $content[Base\Entity::ERROR_CODE] = $responseArrary['code'] ?? null;

            $content[Base\Entity::ERROR_MESSAGE] = $responseArrary['message'] ?? null;

            $content[Base\Entity::GATEWAY_REFERENCE_ID] = $responseArrary['details'] ?? null;

            if ($content[Base\Entity::GATEWAY_REFERENCE_ID] !== null)
            {
                $this->createGatewayPaymentEntity($content, $authenticationGateway, Action::AUTHORIZE);
            }
            else
            {
                $this->trace->info(
                    TraceCode::PAYMENT_AUTH_ESIGN_FAILURE,
                    [
                        'response' => $content
                    ]);
            }

            throw $e;
        }

        return $authenticationResponse;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if (($input['payment']['method'] === 'emandate') and
            ($input['payment']['auth_type'] === 'netbanking'))
        {
            return $this->netbankingCallback($input);
        }

        $enach = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $authenticationGateway = $enach[Base\Entity::AUTHENTICATION_GATEWAY];

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        $this->updateGatewayPaymentEntity($enach, $authResponse, false);

        $data = [];

        if ($input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL)
        {
            $data = $this->getRecurringData();
        }

        return $data;
    }

    protected function netbankingAuthorize($input)
    {
        $this->setCrypto();

        $this->createGatewayPaymentEntity([], 'authorize');

        $request = $this->getAuthRequest($input);

        return $request;
    }

    protected function netbankingCallback($input)
    {
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

            $this->validateCallbackChecksum(
                                            $this->generateHash($secureData),
                                            $input['gateway'][ResponseFields::CHECKSUM]
                                           );

            $attributes = $this->getResponseGatewayAttributes($xmlData);
        }
        else
        {
            $xmlData = $this->getDataFromErrorResponse($responseArray);

            $attributes = $this->getErrorResponseGatewayAttributes($xmlData);
        }

        $this->trace->info(
            TraceCode::GATEWAY_MANDATE_RESPONSE,
            [
                'payment_id'            => $input['payment']['id'],
                'gateway'               => $this->gateway,
                'mandate response data' => $xmlData,
            ]);

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

    protected function getRecurringData()
    {
        $recurringData = [
            Token\Entity::RECURRING_STATUS => Token\RecurringStatus::INITIATED,
        ];

        return $recurringData;
    }

    protected function getGatewayInput(array $input)
    {
        return [
            'next_working_dt' => $this->getNextWorkingDate($input)
        ];
    }

    // @todo: Fix this using the holiday schedule
    protected function getNextWorkingDate(array $input)
    {
        $currentTs = $input['payment']['created_at'];

        $dt = Carbon::createFromTimestamp($currentTs, Timezone::IST);

        // @todo: Move this to a holiday model
        return Holidays::getNextWorkingDay($dt);
    }

    protected function getGatewayTerminalId()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->input['terminal']['gateway_terminal_id'];
        }

        return $this->config['test_terminal_id'];
    }

    public function refund(array $input)
    {
        throw new Exception\RuntimeException(
            'Refund is not implemented');
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

    protected function getDataForXml($input, $secureData)
    {
        $encryptedData = $this->getEncryptedData($secureData);

        $mid = $this->getMerchantId();

        $pid = $input['payment']['id'];

        $mcc = $input['terminal']['category'];

        $creditorAccount = $this->getCreditorAccount();

        $sponserIfsc = $this->getSponsorIfsc();

        $catCode = CategoryCode::getCategoryCodeFromMcc($mcc);

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
                RequestNpciTags::CATEGORY_DESCRIPTION  => CategoryCode::getCategoryDescriptionFromCode($catCode),
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

    public function getMerchantId()
    {
        $mid = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    protected function addHeadersForNpciRequest($request)
    {
        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $request['headers'] = $headers;

        return $request;
    }

    public function verify(array $input)
    {
        if ($input['payment']['auth_type'] === Payment\AuthType::NETBANKING)
        {
            return;
        }

        parent::verify($input);

        // For debit payments, we do not need to verify, since it's file based
        if ($input['payment']['recurring_type'] === Payment\RecurringType::AUTO)
        {
            throw new Exception\PaymentVerificationException(
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $input['payment']['id'],
                    'action'     => 'verify'
                ],
                null,
                VerifyAction::FINISH
            );
        }

        $enach = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE
        );

        $authenticationGateway = $enach[Base\Entity::AUTHENTICATION_GATEWAY];

        return $this->callAuthenticationGateway($input, $authenticationGateway);
    }

    /**
     * @param array $input
     * @param $authenticationGateway
     * @return array
     */
    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        $esignerGatewayResponse = $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);

        return $esignerGatewayResponse;
    }

    private function addChildren($data, $xml)
    {
        foreach ($data as $key => $value)
        {
            $xml->addChild($key,$data[$key]);
        }
    }

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

            ResponseXmlTags::DEBTOR_IFSC        => $responseArray[ResponseXmlTags::MANDATE_ACCEPT_RESPONSE]
                                                                 [ResponseXmlTags::ACCEPT_DETAILS]
                                                                 [ResponseXmlTags::ACCEPT_RESULT]
                                                                 [ResponseXmlTags::DEBTOR]
                                                                 [ResponseXmlTags::DEBTOR_IFSC],
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
            $attr[Entity::REGISTRATION_STATUS]  = RegistrationStatus::SUCCESS;
            $attr[Entity::GATEWAY_REFERENCE_ID] = $data[ResponseXmlTags::ACCEPT_REF_NO];
        }
        else
        {
            $attr[Entity::REGISTRATION_STATUS] = RegistrationStatus::FAILURE;
            $attr[Entity::ERROR_CODE]          = $data[ResponseXmlTags::REJECTION_CODE];
            $attr[Entity::ERROR_MESSAGE]       = $data[ResponseXmlTags::REJECT_DESCRIPTION];
        }

        return $attr;
    }

    protected function getErrorResponseGatewayAttributes($data)
    {
        return [
            Entity::REGISTRATION_STATUS => RegistrationStatus::FAILURE,
            Entity::ERROR_CODE          => $data[ResponseXmlTags::ERROR_CODE],
            Entity::ERROR_MESSAGE       => $data[ResponseXmlTags::ERROR_DESCRIPTION],
        ];
    }

    protected function validateCallbackChecksum($expectedChecksum, $checksum)
    {
        if ($checksum !== $expectedChecksum)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getRecurringDataFromNpciResponse($gatewayPayment)
    {
        $status = $gatewayPayment->getRegistrationStatus();

        if (isset(RegistrationStatus::STATUS_TO_RECURRING_STATUS_MAP[$status]) === false)
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['gateway_payment' => $gatewayPayment->toArray()]);
        }

        $recurringStatus = RegistrationStatus::STATUS_TO_RECURRING_STATUS_MAP[$status];

        $errorCode = $gatewayPayment->getErrorCode();

        $recurringFailureReason = NetbankingErrorCodes::getEmandateRegisterErrorDescriptionFromCode($errorCode);

        $recurringData = [
            Token\Entity::RECURRING_STATUS         => $recurringStatus,
            Token\Entity::RECURRING_FAILURE_REASON => $recurringFailureReason,
        ];

        return $recurringData;
    }

    protected function setCrypto()
    {
        $this->crypto = new Crypto($this->config, $this->mode);
    }

    protected function extractPaymentsProperties($gatewayPayment)
    {
        $response = [];

        // For api based emandate initial payments, if late authorized,
        // we need to update the token status to confirmed
        if (($this->input['payment']['method'] === Payment\Method::EMANDATE) and
            ($this->input['payment'][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL))
        {
            $recurringData = $this->getRecurringData($gatewayPayment);

            $response = array_merge($response, $recurringData);
        }

        return $response;
    }

    protected function getCreditorAccount()
    {
        if ($this->mode === Mode::TEST)
        {
            $credAccount = $this->config['test_emandate_npci_creditor_account'];
        }
        else
        {
            $credAccount = $this->config['live_emandate_npci_creditor_account'];
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
            $sponsor = $this->config['live_emandate_npci_sponser_ifsc'];
        }

        return $sponsor;
    }
}
