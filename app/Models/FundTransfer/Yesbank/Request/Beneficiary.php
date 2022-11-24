<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Network;
use RZP\Models\FundAccount\Type;
use RZP\Models\Settlement\Metric;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Yesbank\RequestConstants;

class Beneficiary extends Base
{
    const RECORD_EXIST = 'Record already exists';

    const RECORD_DOES_NOT_EXIST = 'Record does not exist';

    const RECORD_EXIST_PENDING_APPROVAL = 'Record already exists but pending for approval';

    protected $urlIdentifier;

    protected $ifscCode;

    protected $beneficiaryCd;

    protected $maskedBody = null;

    protected $normalizedBeneName;

    protected $normalizedBankName;

    protected $entityAccountNumber;

    protected $requestTraceCode  = TraceCode::NODAL_BEN_ADD_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_BEN_ADD_RESPONSE;

    protected $responseIdentifier = Constants::BENE_RESPONSE_IDENTIFIER;

    public function __construct(bool $banking = false)
    {
        parent::__construct($banking);

        $this->urlIdentifier = $this->config['ben_add_url_suffix'];
    }

    /**
     * {{@inheritdoc}}
     */
    public function requestBody(): string
    {
         $body =  '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ben="http://BeneMaintenanceService">'
                  . '<soap:Header/>'
                  . '<soap:Body>'
                  . '<ben:maintainBene>'
                  . $this->getContent()
                  . '</ben:maintainBene>'
                  . '</soap:Body>'
                  . '</soap:Envelope>';

         $this->requestTrace = $body;

         //requestTrace can have masked body as well for logging
         if (empty($this->maskedBody) === false)
         {
             $this->requestTrace = $this->maskedBody;
         }

         return $body;
    }

    /**
     * Gives the bene addition content form the bank account
     *
     * @return string
     */
    protected function getContent(): string
    {
        switch ($this->entityType)
        {
            case Type::BANK_ACCOUNT:
                $this->setContentForBankAccount();

                break;

            case Type::CARD:
                $this->setContentForCard();

                break;
        }

        $this->setMaskedBeneficiaryRegisterRequestBody();

        return '<CustId>'
             . $this->customerId
             . '</CustId>'
             . '<BeneficiaryCd>'
             . $this->beneficiaryCd
             . '</BeneficiaryCd>'
             . '<SrcAccountNo>'
             . $this->accountNumber
             . '</SrcAccountNo>'
             . '<PaymentType>'
             . Constants::BENE_PAYMENT_TYPE
             . '</PaymentType>'
             . '<BeneName>'
             . $this->normalizedBeneName
             . '</BeneName>'
             . '<BeneType>'
             . Constants::BENE_TYPE
             . '</BeneType>'
             . '<BankName>'
             . $this->normalizedBankName
             . '</BankName>'
             . '<IfscCode>'
             . $this->ifscCode
             . '</IfscCode>'
             . '<BeneAccountNo>'
             . $this->entityAccountNumber
             . '</BeneAccountNo>'
             . '<Action>'
             . Constants::BENE_FLAG
             . '</Action>';
    }

    /**
     * Process the response from the beneficiary request and report if the bene registration failed
     *
     * @param \WpOrg\Requests\Response $response
     * @return array
     * @throws LogicException
     */
    public function processResponse($response): array
    {
        $responseBody = $this->parseResponseBody($response->body);

        if (($response->status_code !== 200) or
            (isset($responseBody[Constants::BENE_RESPONSE_BODY_IDENTIFIER]) === false))
        {
            throw new LogicException('Invalid response from api', null, $response);
        }

        $responseContent = $responseBody[Constants::BENE_RESPONSE_BODY_IDENTIFIER];

        // Check if response has valid data keys which is required for the processing
        if (isset($responseContent[$this->responseIdentifier]) === false)
        {
            throw new LogicException('Invalid response from api', null, $response);
        }

        $responseContent = $responseContent[$this->responseIdentifier];

        if ($responseContent[Constants::REQUEST_STATUS] !== Constants::SUCCESS)
        {
            $data = $this->extractFailedData($responseContent);

            if ($data[Constants::ERROR] === self::RECORD_EXIST_PENDING_APPROVAL)
            {
                $this->trace->count(Metric::RECORD_EXIST_PENDING_APPROVAL,
                    [
                        'channel'         => $this->channel,
                        'status'          => $data[Constants::ERROR],
                    ]);
            }

            if (($data[Constants::ERROR] !== self::RECORD_EXIST)  and
                ($data[Constants::ERROR] !== self::RECORD_EXIST_PENDING_APPROVAL))
            {
                throw new LogicException($data[Constants::ERROR], null, $data);
            }
            else
            {
                return $data;
            }
        }

        return $this->extractSuccessfulData($responseContent);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContentType(): string
    {
        return 'application/xml';
    }

    /**
     * Parses the soap response in array format
     *
     * @param string $body
     * @return mixed
     */
    protected function parseResponseBody(string $body)
    {
        $xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $body);

        $xml = simplexml_load_string($xml);

        $json = json_encode($xml);

        return json_decode($json,true);
    }

    /**
     * dummy implementation as per the interface.
     * we wont be doing anything on successful bene registration
     * @param array $response
     * @return array
     */
    protected function extractSuccessfulData(array $response): array
    {
        return $response;
    }

    protected function extractGatewayData(array $response): array
    {
        throw new LogicException("should not be implemented for this");
    }

    public function getRequestInputForGateway(): array
    {
        throw new LogicException("should not be implemented for this");
    }

    public function getActionForGateway(): string
    {
        throw new LogicException("should not be implemented for this");
    }

    /**
     * Extract the error data from the failed bene addition response
     *
     * @param array $response
     * @return array
     */
    protected function extractFailedData(array $response): array
    {
        $xml = simplexml_load_string($response[Constants::ERROR], "SimpleXMLElement", LIBXML_NOCDATA);

        $json = json_encode($xml);

        $error = json_decode($json, true);

        return [
            'channel'                   => $this->channel,
            'beneficiary_id'            => $response[Constants::BENEFICIARY_CD],
             Constants::REQUEST_STATUS  => $response[Constants::REQUEST_STATUS],
        ] + $this->getErrorDetails($error[Constants::ITEM]);
    }

    /**
     * Fetches the error details for error response
     *
     * @param array $error
     * @return array
     */
    protected function getErrorDetails(array $error): array
    {
        $message = $error[Constants::REASON] ?? $error[Constants::GENERAL_MESSAGE];

        $code = $error[Constants::ERROR_SUB_CODE];

        return [
            Constants::ERROR => $message,
            'error_code'     => $code,
        ];
    }

    /**
     * Generates successful response for given request
     *
     * @param string $failure
     * @return string
     */
    protected function mockGenerateFailedResponse(string $failure = ''): string
    {
        $errorData = htmlentities(
            '<Error><Item><ErrorSubCode>101</ErrorSubCode>'
                 . '<GeneralMsg>Record already exists</GeneralMsg>'
                 . '</Item></Error>'
        );

        return '<soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">'
            . '<soapenv:Body>'
            . '<NS1:maintainBeneResponse xmlns:NS1="http://BeneMaintenanceService">'
            . '<RequestStatus>'
            . Constants::FAILURE
            . '</RequestStatus>'
            . '<ReqRefNo>'
            . rand(1000, 9999)
            . '</ReqRefNo>'
            . '<Error>'
            . $errorData
            . '</Error>'
            . '<CustId>'
            . $this->customerId
            . '</CustId>'
            . '<BeneficiaryCd>'
            . $this->beneficiaryCd
            . '</BeneficiaryCd>'
            . '<SrcAccountNo>'
            . $this->entityAccountNumber
            . '</SrcAccountNo>'
            . '<PaymentType>'
            . Constants::BENE_PAYMENT_TYPE
            . '</PaymentType>'
            . '<Action>'
            . Constants::BENE_FLAG
            . '</Action>'
            . '</NS1:maintainBeneResponse>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * Generates failed response for given request
     *
     * @return string
     */
    protected function mockGenerateSuccessResponse(): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">'
                . '<soapenv:Body>'
                . '<NS1:maintainBeneResponse xmlns:NS1="http://BeneMaintenanceService">'
                . '<RequestStatus>'
                . Constants::SUCCESS
                . '</RequestStatus>'
                . '<ReqRefNo>'
                . rand(1000, 9999)
                . '</ReqRefNo>'
                . '<Error/>'
                . '<CustId>'
                . $this->customerId
                . '</CustId>'
                . '<BeneficiaryCd>'
                . $this->beneficiaryCd
                . '</BeneficiaryCd>'
                . '<SrcAccountNo>'
                . $this->entityAccountNumber
                . '</SrcAccountNo>'
                . '<PaymentType>'
                . Constants::BENE_PAYMENT_TYPE
                . '</PaymentType>'
                . '<BeneName>Some Name</BeneName>'
                . '<BeneType>V</BeneType>'
                . '<CurrencyCd>INR</CurrencyCd>'
                . '<TransactionLimit>1000</TransactionLimit>'
                . '<BankName>Yes Bank</BankName>'
                . '<IfscCode>IDIB000S110</IfscCode>'
                . '<BeneAccountNo>BA123</BeneAccountNo>'
                . '<UpiHandle>B123@YES</UpiHandle>'
                . '<MobileNo>+911234567890</MobileNo>'
                . '<EmailId>B123@YES.COM</EmailId>'
                . '<AadharNo>123456789012</AadharNo>'
                . '<SwiftCode>A BC DEFgh</SwiftCode>'
                . '<Address1>abcd1234</Address1>'
                . '<Address2>A BC DEF</Address2>'
                . '<Action>'
                . Constants::BENE_FLAG
                . '</Action>'
                . '</NS1:maintainBeneResponse>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
    }

    protected function mockGenerateFailedResponseForGateway(): array
    {
        throw new LogicException("should not be implemented for this");
    }

    protected function mockGenerateSuccessResponseForGateway(): array
    {
        throw new LogicException("should not be implemented for this");
    }

    /**
     * Sets payload content for Bank Account Entity type
     */
    protected function setContentForBankAccount()
    {
        $this->beneficiaryCd = $this->entity->getId();

        $beneName = $this->entity->getBeneficiaryName();

        $this->normalizedBeneName = $this->normalizeBeneficiaryName($beneName);

        $bankName = $this->entity->getBankName();

        $this->normalizedBankName = $this->normalizeBeneficiaryBankName($bankName);

        $this->ifscCode = $this->entity->getIfscCode();

        $this->entityAccountNumber = $this->entity->getAccountNumber();
    }

    /**
     * Sets Payload content for Card Entity type
     * @throws \Exception
     */
    protected function setContentForCard()
    {
        $this->beneficiaryCd = 'card' . $this->entity->getId();

        $beneName = $this->entity->getName();

        $this->normalizedBeneName = $this->normalizeBeneficiaryName($beneName);

        $iin = $this->entity->iinRelation;

        $bankName = $iin->getIssuer();

        $networkCode = $this->entity->getNetworkCode();

        $this->normalizedBankName = $this->normalizeBeneficiaryBankName($bankName);

        $this->ifscCode = $this->getIfscCodeUsingCardInfo($this->entity);

        $vaultToken = $this->getCardVaultToken($this->entity);

        $this->entityAccountNumber = $this->app['card.cardVault']->detokenize($vaultToken);

        if ($networkCode === Network::DICL)
        {
            $this->entityAccountNumber = '00' . $this->entityAccountNumber;
        }
    }

    /**
     * Sets masked body for logging purpose.
     */
    protected function setMaskedBeneficiaryRegisterRequestBody()
    {
        $this->maskedBody = '<CustId>'
            . $this->customerId
            . '</CustId>'
            . '<BeneficiaryCd>'
            . $this->beneficiaryCd
            . '</BeneficiaryCd>'
            . '<SrcAccountNo>'
            . $this->accountNumber
            . '</SrcAccountNo>'
            . '<PaymentType>'
            . Constants::BENE_PAYMENT_TYPE
            . '</PaymentType>'
            . '<BeneName>'
            . $this->normalizedBeneName
            . '</BeneName>'
            . '<BeneType>'
            . Constants::BENE_TYPE
            . '</BeneType>'
            . '<BankName>'
            . $this->normalizedBankName
            . '</BankName>'
            . '<IfscCode>'
            . $this->ifscCode
            . '</IfscCode>'
            . '<BeneAccountNo>'
            . mask_except_last4($this->entityAccountNumber)
            . '</BeneAccountNo>'
            . '<Action>'
            . Constants::BENE_FLAG
            . '</Action>';
    }

    /**
     * Mask the card no/bank account no in response
     * @param $responseBody
     * @return string|string[]|null
     */
    protected function getMaskedResponseBody($responseBody)
    {
        $tagOne = '<BeneAccountNo>';

        $tagTwo = '</BeneAccountNo>';

        $startTagPos = strrpos($responseBody, $tagOne);

        if (empty($startTagPos) === true)
        {
            return $responseBody;
        }

        $startTagPos = $startTagPos + strlen($tagOne);

        $endTagPos = strrpos($responseBody, $tagTwo);

        $maskedStringLength = $endTagPos - $startTagPos;

        $substr = substr($responseBody, $startTagPos, $maskedStringLength);

        $replacement = mask_except_last4($substr);

        return substr_replace($responseBody, $replacement, $startTagPos, $maskedStringLength);
    }
}
