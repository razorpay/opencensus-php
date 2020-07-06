<?php

namespace RZP\Error\P2p;

class PublicErrorDescription extends \RZP\Error\PublicErrorDescription
{
    // @codingStandardsIgnoreStart

    const SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED                = 'Merchant is required in context for the action';
    const SERVER_ERROR_CONTEXT_DEVICE_REQUIRED                  = 'Device is required in context for the action';
    const SERVER_ERROR_CONTEXT_HANDLE_REQUIRED                  = 'Handle is required in context for the action';

    const BAD_REQUEST_INVALID_HANDLE                            = 'Invalid handle is passed in the request';
    const BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE             = 'Device is not registered for the given handle';
    const BAD_REQUEST_MERCHANT_NOT_ALLOWED_ON_HANDLE            = 'Merchant is not allowed to use the handle';
    const BAD_REQUEST_DEVICE_DOES_NOT_BELONG_TO_MERCHANT        = 'Device is not registered for the given merchant';
    const BAD_REQUEST_INVALID_MERCHANT_IN_CONTEXT               = 'Invalid merchant set in context';
    const BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID                   = 'Token is invalid or expired';
    const BAD_REQUEST_DEVICE_BLONGED_TO_OTHER_CUSTOMER          = 'The devices is already registered with other customer';

    const BAD_REQUEST_NO_BANK_ACCOUNT_FOUND                     = 'No account found, please try with different bank';

    const BAD_REQUEST_VPA_NOT_AVAILABLE                         = 'VPA not available, try a different username';
    const BAD_REQUEST_DUPLICATE_VPA                             = 'Duplicate VPA address, try a different username';
    const BAD_REQUEST_MAX_VPA_LIMIT_REACHED                     = 'Maximum VPA allowed per customer limit reached';

    const GATEWAY_ERROR                                         = 'Action could not be completed at bank';

    const GATEWAY_ERROR_DEVICE_INVALID_TOKEN                    = 'Token is invalid or expired';
    const GATEWAY_ERROR_CONNECTION_ERROR                        = 'Unable to connect to bank';

    const BAD_REQUEST_TRANSACTION_INVALID_STATE                 = 'Transaction is not in valid state for update';
    const BAD_REQUEST_DUPLICATE_TRANSACTION                     = 'Duplicate transaction request received';
    const BAD_REQUEST_PAYER_PAYEE_SAME                          = 'Payer/Payee can not belong to same device';

    /* Public error descriptions mapped */
    const BAD_REQUEST_PAYMENT_UPI_RESTRICTED_VPA                       = 'Your transaction has failed as the VPA is restricted to send/receive payments';
    const BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED                = 'You have exceeded the per day transaction limit set by your bank.';
    const BAD_REQUEST_PSP_DOESNT_EXIST                                 = 'Your transaction has failed due to technical error. Try again after sometime.';
    const BAD_REQUEST_PAYMENT_UPI_INVALID_VPA                          = 'Your transaction has failed as the VPA is not valid';
    const BAD_REQUEST_UPI_INVALID_DEVICE_FINGERPRINT                   = 'Your transaction has failed due to technical error. Try re-registering your UPI app';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED              = 'Your transaction has failed as the collect request had expired.';
    const BAD_REQUEST_TRANSACTION_FREQUENCY_LIMIT_EXCEEDED             = 'Your transaction failed as you have exceeded the transaction frequency limit set by your bank';
    const BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED                    = 'Your transaction has failed as you have exceeded the number of MPIN entry attempts.';
    const BAD_REQUEST_PAYMENT_UPI_DEBIT_AND_CREDIT_SAME_ACCOUNT        = 'Your transaction has failed as debit and credit account details cannot be same.';
    const BAD_REQUEST_PAYMENT_PIN_INCORRECT                            = 'Your transaction has failed due to incorrect UPI PIN.';
    const BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT                         = 'Your transaction has failed as your account is either inactive or dormant. Please connect with your bank.';
    const BAD_REQUEST_ACCOUNT_BLOCKED                                  = 'Your transaction has failed as your account is either blocked or frozen.';
    const BAD_REQUEST_PSP_ERROR                                        = 'Your transaction has failed due to your PSP bank is not reachable. Please try after sometime.';
    const BAD_REQUEST_EXPIRED_VPA                                      = 'Your transaction has failed as the VPA has expired.';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED             = 'Your transaction has declined';
    const BAD_REQUEST_FORBIDDEN_TRANSACTION_ON_VPA                     = 'Your transaction has failed as transaction is not permitted to this VPA.';
    const BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID                     = 'Your transaction has declined as card details are not valid. Please connect with your bank';
    const BAD_REQUEST_PAYMENT_UPI_FUNCTION_NOT_SUPPORTED               = 'Your transaction could not be processed due to a technical issue at your bank. Try again after sometime';
    const BAD_REQUEST_UPI_MPIN_NOT_SET                                 = 'Your transaction could not be completed as MPIN for the account is not set.';
    const BAD_REQUEST_INVALID_CARD_DETAILS                             = 'Your transaction has failed as no card record found';

    const GATEWAY_ERROR_DEBIT_FAILED                                   = 'Your transaction could not be processed due to a technical issue at your bank';
    const GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE                        = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_TECHNICAL_ERROR                                = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_DEBIT             = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_NO_ORIGINAL_DEBIT_CREDIT_REQUEST_FOUND         = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_TRANSACTION_DETAILS_MISMATCH                   = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_FUNDS_BLOCKED_BY_MANDATE                       = 'Your transaction failed as funds have been blocked for mandate';
    const GATEWAY_ERROR_DEBIT_TIMEOUT                                  = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_BENEFICIARY_CUTOFF_IN_PROGRESS                 = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_UPI_COOLING_PERIOD_TRANSACTION_LIMIT_EXCEEDS   = 'Your transaction has failed as transaction limit during cooling period has exceeded.';
    const GATEWAY_ERROR_DEBIT_NOT_ALLOWED_FOR_BANK                     = 'Your transaction could not be processed as debit in your account is not allowed by your bank .';
    const GATEWAY_ERROR_FATAL_ERROR                                    = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_ENCRYPTION_ERROR                               = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_REMITTER  = 'Your transaction has been declined due to suspected fraud. Please connect with your bank.';
    const GATEWAY_ERROR_BENEFICIARY_COMPLIANCE_VIOLATION               = 'Your transaction has failed due compliance violation on beneficiary side. Please connect with beneficiary bank.';
    const GATEWAY_ERROR_UPI_PER_DAY_LIMIT_EXCEEDS                      = 'You have exceeded the per day transaction limit set by your bank.';
    const GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT                            = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_BENEFICIARY_ACCOUNT_BLOCKED                    = 'Your transaction has failed as beneficiary account is either blocked or frozen';
    const GATEWAY_ERROR_INVALID_BENEFICIARY_CREDENTIALS                = 'Your transaction could not be processed due to invalid beneficiary credentials';
    const GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_CREDIT            = 'Your transaction has failed due to technical error. Try again after sometime.';
    const GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT            = 'Your transaction failed as there was insufficient balance in your bank account.';
    const GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED                      = 'Transaction to this account is not permitted. Please connect with your bank';
    const GATEWAY_ERROR_DENIED_BY_RISK                                 = 'You have exceeded the per day transaction limit set by your bank.';
    const GATEWAY_ERROR_PSP_NOT_AVAILABLE                              = 'Your transaction has failed due to your PSP bank is not reachable. Please try after sometime.';
    const GATEWAY_ERROR_DO_NOT_HONOUR_REMITTER                         = 'Your transaction has failed from your bank. Please connect with your bank.';
    const GATEWAY_ERROR_REMITTER_COMPLIANCE_VIOLATION                  = 'Your transaction has failed due compliance violation. Please connect with your bank.';
    const GATEWAY_ERROR_BENEFICIARY_TRANSACTION_NOT_PERMITTED          = 'Transaction not permitted to your beneficiary. Please connect with beneficiary bank';
    const GATEWAY_ERROR_INACTIVE_DORMANT_BENEFICIARY_ACCOUNT           = 'Your transaction has failed due to inactive or dormant beneficiary account.';
    const GATEWAY_ERROR_INVALID_FORMAT                                 = 'Your transaction has failed due to technical error.';
    const GATEWAY_ERROR_INTERNAL_FORMAT_ERROR                          = 'Your transaction has failed due to technical error.';
    const GATEWAY_ERROR_CARD_INVALID_AMOUNT                            = 'Your transaction has failed as the amount entered is invalid';

    const GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_BENEFICIARY = 'Your transaction could not be processed due to a technical issue at beneficiary bank';
    const GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_REMITTER    = 'Your transaction could not be processed due to a technical issue at your bank';

    // @codingStandardsIgnoreEnd
}
