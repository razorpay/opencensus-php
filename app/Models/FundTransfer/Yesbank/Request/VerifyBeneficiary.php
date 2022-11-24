<?php


namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Trace\TraceCode;
use RZP\Models\FundAccount\Type;
use RZP\Models\Settlement\Metric;
use RZP\Exception\LogicException;

class VerifyBeneficiary extends Beneficiary
{
    protected $requestTraceCode  = TraceCode::NODAL_BEN_VERIFY_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_BEN_VERIFY_RESPONSE;

    protected $responseIdentifier = Constants::BENE_RESPONSE_IDENTIFIER;

    const ERRORS_TO_RETRY = [
        self::RECORD_EXIST,
        self::RECORD_DOES_NOT_EXIST,
        self::RECORD_EXIST_PENDING_APPROVAL
    ];

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

        $this->setMaskedBeneficiaryVerifyRequestBody();

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
            . '<BeneType>'
            . Constants::BENE_TYPE
            . '</BeneType>'
            . '<IfscCode>'
            . $this->ifscCode
            . '</IfscCode>'
            . '<BeneAccountNo>'
            . $this->entityAccountNumber
            . '</BeneAccountNo>'
            . '<Action>'
            . Constants::VERIFY_BENE_FLAG
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

            if (in_array($data[Constants::ERROR], self::ERRORS_TO_RETRY, true) === false)
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
     * Generates successful response for given request
     *
     * @param string $failure
     * @return string
     */
    protected function mockGenerateFailedResponse(string $failure = ''): string
    {
        $errorData = htmlentities(
            '<Error><Item><ErrorSubCode>101</ErrorSubCode>'
            . '<GeneralMsg>Record does not exist</GeneralMsg>'
            . '</Item></Error>'
        );

        return '<soapenv:Envelope xmlns:soapenv="http://www.w3.org/2003/05/soap-envelope">'
            . '<soapenv:Body>'
            . '<NS1:maintainBeneResponse xmlns:NS1="http://BeneMaintenanceService">'
            . '<RequestStatus>'
            . Constants::FAILURE
            . '</RequestStatus>'
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
            . Constants::VERIFY_BENE_FLAG
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
            . Constants::VERIFY_BENE_FLAG
            . '</Action>'
            . '</NS1:maintainBeneResponse>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    /**
     * Sets masked body for logging purpose.
     */
    protected function setMaskedBeneficiaryVerifyRequestBody()
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
            . '<BeneType>'
            . Constants::BENE_TYPE
            . '</BeneType>'
            . '<IfscCode>'
            . $this->ifscCode
            . '</IfscCode>'
            . '<BeneAccountNo>'
            . mask_except_last4($this->entityAccountNumber)
            . '</BeneAccountNo>'
            . '<Action>'
            . Constants::VERIFY_BENE_FLAG
            . '</Action>';
    }
}
