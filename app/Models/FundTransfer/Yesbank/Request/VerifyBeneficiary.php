<?php


namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Trace\TraceCode;

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
        $beneName = $this->entity->getBeneficiaryName();

        $normalizedBeneName =  $this->normalizeBeneficiaryName($beneName);

        $bankName = $this->entity->getBankName();

        $normalizedBankName =  $this->normalizeBeneficiaryBankName($bankName);

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
            . $normalizedBeneName
            . '</BeneName>'
            . '<BeneType>'
            . Constants::BENE_TYPE
            . '</BeneType>'
            . '<BankName>'
            . $normalizedBankName
            . '</BankName>'
            . '<IfscCode>'
            . $this->entity->getIfscCode()
            . '</IfscCode>'
            . '<BeneAccountNo>'
            . $this->entity->getAccountNumber()
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
            . $this->entity->getId()
            . '</BeneficiaryCd>'
            . '<SrcAccountNo>'
            . $this->entity->getAccountNumber()
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
            . Constants::VERIFY_BENE_FLAG
            . '</Action>'
            . '</NS1:maintainBeneResponse>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }
}
