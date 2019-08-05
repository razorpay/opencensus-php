<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Models\Merchant;
use RZP\Models\Customer\Token;
use RZP\Models\Payment;

class Fields
{
    const MERCHANT_CODE         = 'MerchantCode';
    const DATE                  = 'Date';
    const MERCHANT_REF_NO       = 'MerchantRefNo';
    const CLIENT_CODE           = 'ClientCode';
    const SUCCESS_STATIC_FLAG   = 'SuccessStaticFlag';
    const FAILURE_STATIC_FLAG   = 'FailureStaticFlag';
    const TXN_AMOUNT            = 'TxnAmount';
    const TRANSACTION_ID        = 'TransactionId';
    const FLG_VERIFY            = 'flgVerify';
    const BANK_REF_NO           = 'BankRefNo';
    const FLG_SUCCESS           = 'flgSuccess';
    const MESSAGE               = 'Message';

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
    const DISPLAY_DETAILS           = 'DisplayDetails'; // Always Y
    const DETAILS1                  = 'Details1';
    const DETAILS2                  = 'Details2';
    const DETAILS3                  = 'Details3';

    // The following values are prepended to the details field. They are displayed on the browser to the customer
    const DISPLAY_DEBIT_START_DATE  = 'Debit Start Date';
    const DISPLAY_DEBIT_END_DATE    = 'Debit End Date';
    const DISPLAY_FREQUENCY         = 'Frequency';
    const DISPLAY_MANDATE_AMOUNT    = 'Mandate Amount';
    const DISPLAY_CUSTOMER_NAME     = 'Customer Name';
    const DISPLAY_MANDATE_ID        = 'Mandate Id';

    // Constant values used in E-Mandate registration request
    const CLIENT_NAME       = 'RAZORPAY';
    const AMOUNT_TYPE       = 'Maximum';
    const END_TIMESTAMP     = 'end_timestamp';
    const START_TIMESTAMP   = 'start_timestamp';
    const FREQUENCY         = 'As & when Presented';
    const INIT_AMOUNT       = '1';

    /**
     * Returns values required for e-mandate registration
     *
     * @param Token\Entity $token
     * @param string $paymentId
     * @param Merchant\Entity $merchant
     * @return array
     */
    public static function getEmandateRegistrationData(
        Token\Entity $token,
        string $paymentId,
        Merchant\Entity $merchant
    ): array
    {
        $tokenId = $token->getId();

        $accountNumber = $token->getAccountNumber();

        $merchantName = $merchant->getFilteredDba();

        $customerName = $token['beneficiary_name'] ?? $token->customer->getName();

        return [
            EMandateRegisterFileHeadings::MERCHANT_UNIQUE_REFERENCE_NO  => $paymentId,
            EMandateRegisterFileHeadings::CUSTOMER_NAME                 => $customerName,
            EMandateRegisterFileHeadings::CUSTOMER_ACCOUNT_NUMBER       => $accountNumber,
            EMandateRegisterFileHeadings::FREQUENCY                     => self::FREQUENCY,
            EMandateRegisterFileHeadings::MANDATE_SERIAL_NUMBER         => $tokenId,
            EMandateRegisterFileHeadings::MANDATE_ID                    => $tokenId,
            EMandateRegisterFileHeadings::MERCHANT_REQUEST_NO           => $paymentId,
            EMandateRegisterFileHeadings::AMOUNT_TYPE                   => self::AMOUNT_TYPE,
            EMandateRegisterFileHeadings::CLIENT_NAME                   => self::CLIENT_NAME,
            EMandateRegisterFileHeadings::SUB_MERCHANT_NAME             => $merchantName,
            self::START_TIMESTAMP                                       => $token->getCreatedAt(),
            self::END_TIMESTAMP                                         => $token->getExpiredAt(),
        ];
    }
}
