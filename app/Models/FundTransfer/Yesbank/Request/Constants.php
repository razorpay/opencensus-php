<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

final class Constants
{
    // Bank Response code for successful operation
    const SUCCESS                       = 'SUCCESS';

    // Bank Response code for failed operation
    const FAILURE                       = 'FAILURE';

    //
    // Using this payment type while registering the bene
    // With PAYMENT_TYPE as OTHR we can initiate transfer to all mode
    // Mode has to be specified while initiating payment
    // NEFT - NEFT
    // RTGS - RTGS
    // IFT  - FT
    // UPI  - UPI
    // IMPS - IMPS
    //
    const BENE_PAYMENT_TYPE             = 'OTHR';

    //
    // Flag to be sent for adding beneficiary
    // ADD: add beneficiary
    // MODIFY: modify beneficiary data
    // DELETE: remove a beneficiary
    //
    const BENE_FLAG                     = 'ADD';

    const BENE_RESPONSE_IDENTIFIER      = 'NS1maintainBeneficiaryResponse';

    const BENE_RESPONSE_BODY_IDENTIFIER = 'soapenvBody';

    //
    // Beneficiary type to be used while registering the beneficiary
    // Supported values:
    // V: vendor
    // D: dealer
    // O: other
    //
    const BENE_TYPE                     = 'V';

    // this should be used as value for `TransactionLimit` in verify request
    const MAX_TRANSACTION_LIMIT         = 999999999;

    const CURRENCY                      = 'INR';

    // ******* Constants used in beneficiary registration ******* //
    const REQUEST_STATUS                = 'RequestStatus';

    const ERROR                         = 'Error';

    const BENEFICIARY_CODE              = 'BeneficiaryCd';

    const ITEM                          = 'Item';

    const REASON                        = 'Reason';

    const GENERAL_MESSAGE               = 'GeneralMsg';

    const ERROR_SUB_CODE                = 'ErrorSubCode';
}
