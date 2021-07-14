<?php

namespace RZP\Gateway\Enach\Citi;

use RZP\Models\Merchant;
use RZP\Gateway\Enach\Base;
use RZP\Models\PaperMandate;
use RZP\Models\Customer\Token;

class Fields
{
    // Constant values used in NACH debit request
    const ACH_TRANSACTION_CODE                            = '67';
    const CONTROL_9                                       = '         ';
    const LEDGER_FOLIO_NUMBER                             = '   ';
    const CONTROL_15                                      = '               ';
    const CONTROL_7                                       = '       ';
    const CONTROL_13                                      = '             ';
    const ACH_ITEM_SEQ_NUMBER                             = '          ';
    const CHECK_SUM                                       = '          ';
    const FLAG                                            = ' ';
    const REASON_CODE                                     = '  ';
    const BENEFICIARY_AADHAR_NUMBER                       = '               ';
    const FILLER                                          = '       ';
    const END_TIMESTAMP                                   = 'end_timestamp';
    const START_TIMESTAMP                                 = 'start_timestamp';
    const FREQUENCY                                       = 'As & when Presented';

    //Constant values used in Presentation file headings
    const ACH_TRANSACTION_CODE_HEADING                    = '56';
    const CONTROL_7_HEADING                               = '       ';
    const USERNAME_HEADING                                = 'RAZORPAY SOFTWARE PVT LTD               ';
    const ACH_FILE_NUMBER_HEADING                         = '         ';
    const CONTROL_14_HEADING                              = '              ';
    const CONTROL_9_HEADING                               = '         ';
    const CONTROL_15_HEADING                              = '               ';
    const LEDGER_FOLIO_NUMBER_HEADING                     = '   ';
    const ACH_ITEM_SEQ_NUMBER_HEADING                     = '          ';
    const CHECK_SUM_HEADING                               = '          ';
    const FILLER_3                                        = '   ';
    const USER_DEFINED_LIMIT_FOR_INDIVIDUAL_ITEMS         = '0001000000000';
    const SAVINGS                                         = 'savings';
    const CURRENT                                         = 'current';
    const USER_REFERENCE_HEADING                          = '000000000000000000';
    const USER_BANK_ACCOUNT_NUMBER_HEADING                = '000018003                          ';
    const SETTLEMENT_CYCLE_HEADING                        = '  ';
    const FILLER_57                                       = '                                                         ';
    const CLIENT_CODE                                     = 'CTRAZORPAY';

    const ACCOUNT_TYPE_VALUE                             =  'accountType' ;
    const ACCOUNT_NAME                                   =  'accountName';
    const USERNAME                                       =  'userName';
    const AMOUNT                                         =  'amount';
    const IFSC                                           =  'ifsc';
    const ACCOUNT_NUMBER                                 =  'accountNumber';
    const UMRN                                           =  'umrn';
    const UTILITY_CODE                                   =  'utilityCode';
    const TRANSACTION_REFERENCE                          =  'transactionReference';
    const SPONSER_BANK                                   =  'sponserBank';

    /**
     * Returns values required for Nach registration
     *
     * @param Token\Entity $token
     * @param string $paymentId
     * @param Merchant\Entity $merchant
     * @param PaperMandate\Entity $paperMandate
     * @return array
     */
    public static function getNachRegistrationData(
        Token\Entity $token,
        string $paymentId,
        Merchant\Entity $merchant,
        PaperMandate\Entity $paperMandate
    ): array
    {
        $merchantCategory = $merchant->getCategory();

        $bankName = $token['bank'];

        $accountNumber = $token->getAccountNumber();

        $ifsc = $token->getIfsc();

        $accountType = Fields::getAccountTypeMapping($token->getAccountType());

        $categoryCode = Base\CategoryCode::getCategoryCodeFromMcc($merchantCategory);

        $categoryDescription = Base\CategoryCode::getCategoryDescriptionFromCode($categoryCode);

        $customerName = $token['beneficiary_name'] ?? $token->customer->getName();

        $customerName = substr($customerName, 0, 40);

        $startDate = $paperMandate->getStartAt();

        $endDate = $paperMandate->getEndAt();

        return [
            NachRegisterFileHeadings::CATEGORY_CODE                 => $categoryCode,
            NachRegisterFileHeadings::CATEGORY_DESCRIPTION          => $categoryDescription,
            NachRegisterFileHeadings::CLIENT_CODE                   => self::CLIENT_CODE,
            NachRegisterFileHeadings::MERCHANT_UNIQUE_REFERENCE_NO  => $paymentId,
            NachRegisterFileHeadings::CUSTOMER_ACCOUNT_NUMBER       => $accountNumber,
            NachRegisterFileHeadings::CUSTOMER_NAME                 => $customerName,
            NachRegisterFileHeadings::ACCOUNT_TYPE                  => $accountType,
            NachRegisterFileHeadings::BANK_NAME                     => $bankName,
            NachRegisterFileHeadings::BANK_IFSC                     => $ifsc,
            self::START_TIMESTAMP                                   => $startDate,
            self::END_TIMESTAMP                                     => $endDate,
        ];
    }

    public static function getAccountTypeMapping(string $accountType): string
    {
        $accountTypeMap = [
            Token\Entity::ACCOUNT_TYPE_SAVINGS     => 'savings',
            Token\Entity::ACCOUNT_TYPE_CURRENT     => 'current',
            Token\Entity::ACCOUNT_TYPE_CASH_CREDIT => 'cc',
            Token\Entity::ACCOUNT_TYPE_SB_NRE      => 'savings',
            Token\Entity::ACCOUNT_TYPE_SB_NRO      => 'savings',
        ];

        return $accountTypeMap[$accountType] ?? 'savings';
    }
}
