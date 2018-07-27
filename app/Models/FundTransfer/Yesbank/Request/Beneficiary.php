<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Yesbank\RequestConstants;

class Beneficiary extends Base
{
    const RECORD_EXIST = 'Record already exists';

    protected $urlIdentifier;

    protected $requestTraceCode  = TraceCode::NODAL_BEN_ADD_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_BEN_ADD_RESPONSE;

    protected $responseIdentifier = Constants::BENE_RESPONSE_IDENTIFIER;

    public function __construct()
    {
        parent::__construct();

        $this->urlIdentifier = $this->config['ben_add_url_suffix'];
    }

    /**
     * {{@inheritdoc}}
     */
    public function requestBody(): string
    {
         return '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:ben="http://BeneMaintenanceService">'
                . '<soap:Header/>'
                . '<soap:Body>'
                . '<ben:maintainBene>'
                . $this->getContent()
                . '</ben:maintainBene>'
                . '</soap:Body>'
                . '</soap:Envelope>';
    }

    /**
     * Gives the bene addition content form the bank account
     *
     * @return string
     */
    protected function getContent(): string
    {
        $beneName = $this->entity->getBeneficiaryName();

        $normalizedName =  $this->normalizeBeneficiaryName($beneName);

        return '<CustId>'
             . $this->customerId
             . '</CustId>'
             . '<BeneficiaryCd>'
             . $this->entity->getId()
             . '</BeneficiaryCd>'
             . '<SrcAccountNo>'
             . $this->accountNumber
             . '</SrcAccountNo>'
             . '<PaymentType>'
             . Constants::BENE_PAYMENT_TYPE
             . '</PaymentType>'
             . '<BeneName>'
             . $normalizedName
             . '</BeneName>'
             . '<BeneType>'
             . Constants::BENE_TYPE
             . '</BeneType>'
             . '<BankName>'
             . $this->entity->getBankName()
             . '</BankName>'
             . '<IfscCode>'
             . $this->entity->getIfscCode()
             . '</IfscCode>'
             . '<BeneAccountNo>'
             . $this->entity->getAccountNumber()
             . '</BeneAccountNo>'
             . '<Action>'
             . Constants::BENE_FLAG
             . '</Action>';
    }

    /**
     * Process the response from the beneficiary request and report if the bene registration failed
     *
     * @param \Requests_Response $response
     * @return array
     * @throws LogicException
     */
    public function processResponse(\Requests_Response $response): array
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

            if($data['error'] !== self::RECORD_EXIST)
            {
                throw new LogicException($data['error'], TraceCode::BENEFICIARY_REGISTRATION_FAILED_RESPONSE, $data);
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
     */
    protected function extractSuccessfulData(array $response): array
    {
        // do nothing here as we are not doing anything with this data
        return [];
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
            'channel'        => $this->channel,
            'beneficiary_id' => $response[Constants::BENEFICIARY_CD],
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
            'error'          => $message,
            'error_code'     => $code,
        ];
    }

    /**
     * Generates successful response for given request
     *
     * @return string
     */
    protected function mockGenerateFailedResponse(): string
    {
        return '<soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">'
            . '<soapenv:Body>'
            . '<NS1:maintainBeneficiaryResponse xmlns:NS1="http://BeneMaintenanceService">'
            . '<RequestStatus>'
            . Constants::FAILURE
            . '</RequestStatus>'
            . '<ReqRefNo>'
            . rand(1000, 9999)
            . '</ReqRefNo>'
            . '<Error>'
            . '<![CDATA[<Error>'
            . '<Item><ErrorSubCode>101</ErrorSubCode><GeneralMsg>Record already exists</GeneralMsg></Item>'
            . '</Error>]]>'
            . '</Error>'
            . '<CustId>'
            . $this->customerId
            . '</CustId>'
            . '<BeneficiaryCd>'
            . $this->entity->getId()
            . '</BeneficiaryCd>'
            . '<SrcAccountNo>'
            . $this->entity->getAccountNumber()
            . '</SrcAccountNo>'
            . '<PaymentType>'
            . Constants::BENE_PAYMENT_TYPE
            . '</PaymentType>'
            . '<Action>'
            . Constants::BENE_FLAG
            . '</Action>'
            . '</NS1:maintainBeneficiaryResponse>'
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
                . '<NS1:maintainBeneficiaryResponse xmlns:NS1="http://BeneMaintenanceService">'
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
                . $this->entity->getId()
                . '</BeneficiaryCd>'
                . '<SrcAccountNo>'
                . $this->entity->getAccountNumber()
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
                . '</NS1:maintainBeneficiaryResponse>'
                . '</soapenv:Body>'
                . '</soapenv:Envelope>';
    }
}
