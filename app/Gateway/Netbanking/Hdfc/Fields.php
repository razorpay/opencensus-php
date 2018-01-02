<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Models\Customer\Token;

class Fields
{
    const MERCHANT_CODE         = 'MerchantCode' ;
    const DATE                  = 'Date' ;
    const MERCHANT_REF_NO       = 'MerchantRefNo' ;
    const CLIENT_CODE           = 'ClientCode' ;
    const SUCCESS_STATIC_FLAG   = 'SuccessStaticFlag' ;
    const FAILURE_STATIC_FLAG   = 'FailureStaticFlag' ;
    const TXN_AMOUNT            = 'TxnAmount' ;
    const TRANSACTION_ID        = 'TransactionId' ;
    const FLG_VERIFY            = 'flgVerify' ;
    const BANK_REF_NO           = 'BankRefNo' ;
    const FLG_SUCCESS           = 'flgSuccess' ;
    const MESSAGE               = 'Message' ;

    // Request field name used in E-Mandate registration
    const CLIENT_ACCOUNT_NUMBER     = 'ClientAccNum';   // Customer's account number
    const REF1                      = 'Ref1';           // Merchant unique reference no.
    const REF2                      = 'Ref2';           // Customer's name
    const REF3                      = 'Ref3';           // Customer's account number
    const REF4                      = 'Ref4';           // Payment amount in Rupees
    const REF5                      = 'Ref5';           // Frequency
    const REF6                      = 'Ref6';           // Mandate serial number
    const REF7                      = 'Ref7';           // Mandate ID
    const REF8                      = 'Ref8';           // Merchant request number
    const REF9                      = 'Ref9';           // Amount type
    const REF10                     = 'Ref10';          // Client name
    const DATE1                     = 'Date1';          // Start date
    const DATE2                     = 'Date2';          // End date

    // Constant values used in E-Mandate registration request
    const CLIENT_NAME       = 'RAZORPAY';
    const AMOUNT_TYPE       = 'Maximum';
    const END_TIMESTAMP     = 'end_timestamp';
    const START_TIMESTAMP   = 'start_timestamp';
    const FREQUENCY         = 'As & when Presented';

    /**
     * Returns values required for e-mandate registration
     *
     * @param Token\Entity $token
     *
     * @return array
     */
    public static function getEMandateRegistrationData(Token\Entity $token): array
    {
        $tokenId = $token->getId();

        $accountNumber = $token->getAccountNumber();

        $customerName = $token->customer->getName();

        return [
            EMandateRegisterFileHeadings::MERCHANT_UNIQUE_REFERENCE_NO  => $tokenId,
            EMandateRegisterFileHeadings::CUSTOMER_NAME                 => $customerName,
            EMandateRegisterFileHeadings::CUSTOMER_ACCOUNT_NUMBER       => $accountNumber,
            EMandateRegisterFileHeadings::FREQUENCY                     => self::FREQUENCY,
            EMandateRegisterFileHeadings::MANDATE_SERIAL_NUMBER         => $tokenId,
            EMandateRegisterFileHeadings::MANDATE_ID                    => $tokenId,
            EMandateRegisterFileHeadings::MERCHANT_REQUEST_NO           => $tokenId,
            EMandateRegisterFileHeadings::AMOUNT_TYPE                   => self::AMOUNT_TYPE,
            EMandateRegisterFileHeadings::CLIENT_NAME                   => self::CLIENT_NAME,
            self::START_TIMESTAMP                                       => $token->getCreatedAt(),
            self::END_TIMESTAMP                                         => 4102338600, // timestamp for 31st Dec 2099
        ];
    }
}