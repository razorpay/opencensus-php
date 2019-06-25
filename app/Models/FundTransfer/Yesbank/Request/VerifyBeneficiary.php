<?php


namespace RZP\Models\FundTransfer\Yesbank\Request;

class VerifyBeneficiary extends Beneficiary
{

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
}
