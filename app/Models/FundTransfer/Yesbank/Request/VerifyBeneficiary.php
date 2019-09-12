<?php


namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Trace\TraceCode;
use RZP\Models\FundAccount\Type;

class VerifyBeneficiary extends Beneficiary
{
    protected $requestTraceCode  = TraceCode::NODAL_BEN_VERIFY_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_BEN_VERIFY_RESPONSE;

    protected $responseIdentifier = Constants::BENE_RESPONSE_IDENTIFIER;
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
            . Constants::VERIFY_BENE_FLAG
            . '</Action>';
    }


    /**
     * Generates successful response for given request
     *
     * @return string
     */
    protected function mockGenerateFailedResponse(): string
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
            . Constants::VERIFY_BENE_FLAG
            . '</Action>';
    }
}
