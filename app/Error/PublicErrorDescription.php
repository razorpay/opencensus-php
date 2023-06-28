<?php

namespace RZP\Error;

class PublicErrorDescription
{
    //
    // Please note before declaring strings that have characters that require escaping.
    // Serialization is a problem when using these characters where signing is involved.
    // Because the other side may read the backslashes as escape characters and ignore
    // them while generating the signature.
    // As per JSON spec these need escaping -
    //        %x22 /          ; "    quotation mark  U+0022
    //        %x5C /          ; \    reverse solidus U+005C
    //        %x2F /          ; /    solidus         U+002F
    //        %x62 /          ; b    backspace       U+0008
    //        %x66 /          ; f    form feed       U+000C
    //        %x6E /          ; n    line feed       U+000A
    //        %x72 /          ; r    carriage return U+000D
    //        %x74 /          ; t    tab             U+0009
    //

    const BAD_REQUEST_INVALID_AUTH_TYPE                                         = 'Auth type is invalid.';
    const BAD_REQUEST_REFUND_RECEIPT_ALREADY_PRESENT                            = 'Duplicate receipt found for this refund request.';
    const GATEWAY_ERROR                                                         = 'Payment processing failed due to error at bank or wallet gateway';
    const SERVER_ERROR                                                          = 'We are facing some trouble completing your request at the moment. Please try again shortly.';
    const GATEWAY_ERROR_REQUEST_TIMEOUT                                         = 'The gateway request to submit payment information timed out. Please submit your details again';
    const GATEWAY_ERROR_PROCESSING_DECLINED                                     = 'Payment failed due to processing error on gateway';
    const GATEWAY_ERROR_SYSTEM_BUSY                                             = 'Gateway system is busy, please retry.';
    const GATEWAY_ERROR_COMMUNICATION_ERROR                                     = 'Gateway experienced a communication error.';
    const GATEWAY_ERROR_USER_INACTIVE                                           = 'User is inactive.';
    const GATEWAY_ERROR_PAYMENT_BIN_CHECK_FAILED                                = 'Card rejected by bank.';
    const GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR                            = 'Payment failed because card holder couldn\'t be authenticated';
    const GATEWAY_ERROR_FALSE_AUTHORIZE                                         = 'The payment was wrongly authorized';
    const GATEWAY_ERROR_REFUND_DUPLICATE_REQUEST                                = 'Duplicate Refund Request';
    const GATEWAY_ERROR_ONBOARDING_FAILED                                       = 'Merchant boarding on gateway failed.';
    const GATEWAY_ERROR_TERMINAL_ONBOARDING_FAILED                              = 'Terminal onboarding failed on gateway';

    const GATEWAY_ERROR_TERMINAL_DISABLE_FAILED                                 = 'Terminal disable failed on gateway';
    const GATEWAY_ERROR_TERMINAL_ENABLE_FAILED                                  = 'Terminal enable failed on gateway';
    const GATEWAY_ERROR_CARD_NOT_ENROLLED                                       = 'Your payment could not be completed as this card is not enabled for online payments. Try another payment method or contact your bank for details.';
    const GATEWAY_ERROR_AUTHENTICATION_STATUS_ATTEMPTED                         = 'Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.';
    const GATEWAY_ERROR_AUTHENTICATION_STATUS_FAILED                            = '3D Secure authentication failed';
    const BAD_REQUEST_CARD_DISABLED_FOR_ONLINE_PAYMENTS                         = 'Your card has been disabled for online payments by your issuing bank. Please reach out to your bank or re-try the payment with another card/method.';
    const BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST                           = 'Different request body sent for the same Idempotency Header';
    const BAD_REQUEST_PAYMENT_CARD_NOT_LINKED_WITH_MOBILE                       = 'Card is not linked with mobile number';
    const BAD_REQUEST_ERROR                                                     = 'Something went wrong, please try again after sometime.';
    const BAD_REQUEST_INVALID_PASSWORD_RESET_TOKEN                              = 'The reset link has expired or invalid';
    const BAD_REQUEST_PAYOUTS_NOT_ALLOWED_CURRENTLY                             = 'Payouts are temporarily blocked. Please contact support.';
    const BAD_REQUEST_MERCHANT_NOT_ENABLED_FOR_2FA_PAYOUT                       = 'ICICI 2FA payout is not enabled.';
    const BAD_REQUEST_MERCHANT_NOT_ALLOWED_TO_TRIGGER_2FA_OTP                   = 'ICICI 2FA payout OTP creation is not allowed.';
    const BAD_REQUEST_BLOCKING_RX_ACTIVATIONS                                   = 'RazorpayX activations are currently blocked';
    const BAD_REQUEST_FAV_NOT_ALLOWED_CURRENTLY                                 = 'Fund Account Validations are temporarily blocked. Please contact support.';
    const BAD_REQUEST_CHANGE_PASSWORD_NOT_ALLOWED                               = 'Password Change is not allowed for this Org';
    const BAD_REQUEST_PASSWORD_ALREADY_SET                                      = 'Password is already set for this user';
    const BAD_REQUEST_ACCESS_TOKEN_INVALID                                      = 'Access Token is not valid or expired.';
    const BAD_REQUEST_URL_NOT_FOUND                                             = 'The requested URL was not found on the server.';
    const BAD_FEATURE_PERMISSION_NOT_FOUND                                      = "You do not have permission to access this feature.";
    const BAD_REQUEST_PAYMENT_NOT_FOUND                                         = 'The requested payment was not found on the server';
    const BAD_REQUEST_ROUTE_DISABLED                                            = 'The requested route is disabled.';
    const BAD_REQUEST_ONLY_HTTPS_ALLOWED                                        = 'Razorpay API is only available over HTTPS.';
    const BAD_REQUEST_FORBIDDEN                                                 = 'Access forbidden for requested resource';
    const BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED                                   = 'The current http method is not supported';
    const BAD_REQUEST_INVALID_ID                                                = 'The id provided does not exist';
    const BAD_REQUEST_INVALID_TERMINAL_ID                                       = 'The id must be of length 14';
    const BAD_REQUEST_INVALID_IDS                                               = 'One or more of the ids provided does not exist';
    const BAD_REQUEST_INVALID_QUERY                                             = 'The query is invalid or is not allowed';
    const BAD_REQUEST_NO_RECORDS_FOUND                                          = 'No db records found.';
    const BAD_REQUEST_INVALID_BANK_FOR_EMANDATE                                 = 'Invalid bank passed for E-mandate payment';
    const BAD_REQUEST_PAYMENT_FAILED                                            = 'Payment failed';
    const BAD_REQUEST_BQR_PAYMENT_FAILED                                        = 'Payment failed';
    const BAD_REQUEST_DECRYPTION_FAILED                                         = 'Decryption failed';
    const BAD_REQUEST_SIGNING_KEY_EXPIRED                                       = 'The signing key has expired';
    const BAD_REQUEST_MESSAGE_EXPIRED                                           = 'The payload has expired';
    const BAD_REQUEST_MERCHANT_ID_DOES_NOT_MATCH                                = 'Gateway Merchant Id does not match of the payment';
    const BAD_REQUEST_AMOUNT_MISMATCH                                           = 'The amount does not match with payment amount';
    const BAD_REQUEST_PAYMENT_CANCELLED_BY_USER                                 = 'Payment processing cancelled by user';
    const BAD_REQUEST_PAYMENT_CANCELLED_BY_PRESSING_BACK_ON_ANDROID             = 'Payment processing cancelled by pressing back button on android';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_LOGIN_SCREEN                         = 'Payment processing cancelled by customer at login screen';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE                  = 'Payment processing cancelled by customer at wallet payment page';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE              = 'Payment processing cancelled by customer at netbanking payment page';
    const BAD_REQUEST_RATE_LIMIT_EXCEEDED                                       = 'Request failed. Please try after sometime.';
    const BAD_REQUEST_BOTH_TOKENS_PRESENT                                       = 'Approve and Reject tokens both present.';
    const BAD_REQUEST_CONFLICT_ALREADY_EXISTS                                   = 'Duplicate request. This request has already been processed.';
    const BAD_REQUEST_PAYMENT_ALREADY_PROCESSED                                 = 'The payment has already been processed';
    const BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER                            = 'Card mandate created for payment has been cancelled by user';
    const BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE                                = 'Card mandate is not active';
    const BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_CANCELLED                      = 'Card mandate is not active, it is cancelled by user';
    const BAD_REQUEST_AMOUNT_GREATER_THAN_CARD_MANDATE_MAX_AMOUNT               = 'Payment amount is greater than card mandate\'s max amount';
    const BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_PAUSED                         = 'Card mandate is not active, it is paused by user';
    const BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_EXPIRED                        = 'Card mandate is not active, it is expired';
    const BAD_REQUEST_CARD_MANDATE_MAXIMUM_ALLOWED_DEBIT_EXCEEDED_IN_CURRENT_CYCLE = 'Maximum allowed debits in current cycle exceeded';
    const BAD_REQUEST_CARD_MANDATE_DEBIT_DATE_OUT_OF_RANGE                      = 'Debit date out of range';
    const BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE                           = 'Mandate not active';
    const BAD_REQUEST_CARD_MANDATE_PROMISED_DEBIT_DATE_NOT_HONOURED             = 'Promised debit date not honoured';
    const BAD_REQUEST_CARD_MANDATE_PAYMENT_ATTEMPTED_BEFORE_MIN_GAP_OF_NOTIFICATION = 'Payment done before 24 hours from notification delivery time';
    const BAD_REQUEST_CARD_MANDATE_PAYMENT_DEBIT_NOT_AS_PER_FREQUENCY           = 'Mandate debit not as per frequency';
    const BAD_REQUEST_CARD_MANDATE_NOTIFICATION_ALREADY_USED                    = 'notification already used';
    const BAD_REQUEST_CARD_MANDATE_NOTIFICATION_PAYMENT_AMOUNT_MISMATCH         = 'notification amount does not match with payment amount';
    const BAD_REQUEST_CARD_MANDATE_NOTIFICATION_PAYMENT_CURRENCY_MISMATCH       = 'notification currency does not match with payment currency';
    const BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_NOTIFIED                        = 'customer not notified';
    const BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_APPROVED                        = 'customer not approved the debit';
    const BAD_REQUEST_CARD_MANDATE_CUSTOMER_OPTED_OUT_OF_PAYMENT                = 'customer opted out of the payment';
    const BAD_REQUEST_PAYMENT_ALREADY_CAPTURED                                  = 'This payment has already been captured';
    const BAD_REQUEST_PAYMENT_ALREADY_CAPTURED_OR_VOIDED                        = 'The payment has already been either captured or voided';
    const BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED                            = 'Currency is not supported';
    const BAD_REQUEST_PAYMENT_METHOD_NOT_TRANSFER                               = 'The payment method should be transfer for action to be taken';
    const BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED                               = 'The payment status should be captured for action to be taken';
    CONST BAD_REQUEST_PAYMENT_ALREADY_SETTLED                                   = "The payment is already settled for the merchant.";
    const BAD_REQUEST_PAYMENT_STATUS_CAPTURE_NOT_PROCESSED                      = 'Capture request is not processed yet';
    const BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT                          = 'Payout cannot be created on a payment that has not been settled to your account';
    const BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED               = 'The payout amount provided is greater than the payment amount captured';
    const BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_PENDING                = 'The payout amount provided is greater than the payout amount pending for the payment';
    const BAD_REQUEST_PAYOUT_FUND_TRANSFER_ON_CREDIT_CARD_PAYMENT               = 'Payouts of method fund_transfer cannot be created on Credit Card payments';
    const BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH                               = 'Given method / mode cannot be used for the payout amount specified';
    const BAD_REQUEST_AT_LEAST_ONE_OF_EMAIL_OR_PHONE_REQUIRED                   = 'Cannot add contact when both email and phone number are missing.';
    const BAD_REQUEST_INVALID_FUND_ACCOUNT_ID                                   = 'Fund Account passed does not belong to intended contact';
    const BAD_REQUEST_FTA_AMOUNT_MODE_MISMATCH                                  = 'Given mode cannot be used for the amount given';
    const BAD_REQUEST_PAYOUT_RETRY_FOR_PAYMENT                                  = 'Payout retry is not allowed only for payment payouts';
    const BAD_REQUEST_PAYOUT_RETRY_NOT_IN_REVERSED                              = 'Payout retry is allowed only for reversed payouts';
    const BAD_REQUEST_PAYMENT_FULLY_PAIDOUT                                     = 'The payment has been fully paidout already';
    const BAD_REQUEST_GATEWAY_TOKEN_EMPTY                                       = 'Invalid token has been passed for recurring payment';
    const BAD_REQUEST_TOKEN_NOT_ENABLED_FOR_RECURRING                           = 'Invalid token has been passed for recurring payment';
    const BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS                     = 'Request failed because another payment operation is in progress';
    const BAD_REQUEST_TOKEN_UPDATION_OPERATION_IN_PROGRESS                      = 'Request failed because token updation is in progress';
    const SERVER_ERROR_ANOTHER_OPERATION_PROGRESS_SAME_IDEM_KEY                 = 'Request failed because another request is in progress with the same Idempotency Key';
    const SERVER_ERROR_CACHE_DATA_MISSING_FOR_BANK_ACCOUNT_UPDATE               = 'Cache data missing for Bank Account Update';
    const BAD_REQUEST_UPDATE_EXPIRED_TOKEN                                      = 'Token updation failed because token is expired';
    const BAD_REQUEST_UPDATE_NOT_CONFIRMED_TOKEN                                = 'Token updation failed because token is not confirmed';
    const BAD_REQUEST_ANOTHER_FTA_RECONCILIATION_OPERATION_IN_PROGRESS          = 'Request failed because another operation is in progress';
    const BAD_REQUEST_ANOTHER_FTA_TRANSFER_OPERATION_IN_PROGRESS                = 'Request failed because another operation is in progress';
    const BAD_REQUEST_PAYMENT_FULLY_REFUNDED                                    = 'The payment has been fully refunded already';
    const BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED               = 'The refund amount provided is greater than amount captured';
    const BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT    = 'The total refund amount is greater than the refund payment amount';
    const BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED                  = 'The refund on this payment is blocked due to ongoing dispute investigation';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT                       = 'Minimum transaction amount allowed is Re. 1';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_10_MIN_AMOUNT                    = 'Minimum transaction amount allowed is Rs 10';
    const BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH                                   = 'The amount entered is too high';
    const BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH_DECLINED_BY_ISSUER                = 'The amount entered is too high. Declined by issuing bank';
    const BAD_REQUEST_PAYMENT_ATOM_NET_BANKING_MIN_AMOUNT_FIFTY                 = 'Minimum amount allowed for net banking transaction for the merchant is INR 50';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT_FOR_EMI               = 'Minimum transaction amount allowed is Rs 2000';
    const BAD_REQUEST_PAYMENT_CARD_IS_NOT_ARRAY                                 = 'Card provided is not a dictionary';
    const BAD_REQUEST_PAYMENT_CARD_NOT_PROVIDED                                 = 'Payment Exception: Card not provided';
    const BAD_REQUEST_PAYMENT_CARD_CVV_NOT_PROVIDED                             = 'Payment Exception: Card cvv not provided';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_CVV                                  = 'Payment failed due to incorrect card CVV';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_PIN                                  = 'Payment failed';
    const BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED                 = 'Your payment could not be completed due to incorrect OTP or verification details. Try another payment method or contact your bank for details.';
    const BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_NOT_AVAILABLE                 = 'Payment failed because cardholder couldn\'t be authenticated';
    const BAD_REQUEST_PAYMENT_NET_BANKING_NOT_ENABLED                           = 'Net banking is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED                              = 'Wallet is not supported';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_PROVIDED                               = 'Wallet is not provided';
    const BAD_REQUEST_PAYMENT_RECURRING_NOT_ENABLED                             = 'Recurring payment is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_RECURRING_AUTH_NOT_SUPPORTED                      = 'recurring payment is not supported on public auth';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT                   = 'Wallet is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT                     = 'Card transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_EMI_NOT_ENABLED_FOR_MERCHANT                      = 'Emi transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_CARDLESS_EMI_NOT_ENABLED_FOR_MERCHANT             = 'Cardless Emi transactions are not supported for the merchant';
    const BAD_REQUEST_PAYMENT_COD_NOT_ENABLED_FOR_MERCHANT                      = 'Cash on delivery transactions are not supported for the merchant';
    const BAD_REQUEST_PAYMENT_PAYLATER_NOT_ENABLED_FOR_MERCHANT                 = 'Pay Later transactions are not supported for the merchant';
    const BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD                         = 'Emi is not available for the card used in the transaction';
    const BAD_REQUEST_PAYMENT_AEPS_NOT_ENABLED_FOR_MERCHANT                     = 'Aeps transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_EMANDATE_NOT_ENABLED_FOR_MERCHANT                 = 'E-Mandate transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT                      = 'UPI transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_NOT_ENABLED_FOR_MERCHANT              = 'UPI collect transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_UPI_INTENT_NOT_ENABLED_FOR_MERCHANT               = 'UPI intent transactions are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT            = 'Bank transfers are not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_OFFLINE_NOT_ENABLED_FOR_MERCHANT                  = 'Offline method is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_BHARAT_QR_NOT_ENABLED_FOR_MERCHANT                = 'Bharat Qr is not enabled for merchant';
    const BAD_REQUEST_PAYMENT_VIRTUAL_VPA_NOT_ENABLED_FOR_MERCHANT              = 'Virtual VPA is not enabled for merchant';
    const BAD_REQUEST_PAYMENT_BANK_NOT_PROVIDED                                 = 'Bank not provided for net banking payment';
    const BAD_REQUEST_PAYMENT_INVALID_BANK_CODE                                 = 'Bank code provided for net banking payment is invalid';
    const BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE                      = 'Account Balance is insufficient';
    const BAD_REQUEST_INVALID_USER_CREDENTIALS                                  = 'Payment failed due to invalid user credentials';
    const BAD_REQUEST_APP_TOKEN_ABSENT                                          = 'Customer not logged in';
    const BAD_REQUEST_APP_TOKEN_NOT_GLOBAL                                      = 'Global customer not logged in';
    const BAD_REQUEST_PAYMENT_WALLET_CUSTOMER_TOKEN_NOT_FOUND                   = 'Payment failed';
    const BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT                          = 'Contact number contains invalid characters, only digits and + symbol are allowed';
    const BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE                      = 'Contact number contains invalid country code';
    const BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT                                 = 'Contact number should be at least 8 digits, including country code';
    const BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG                                  = 'Contact number should not be greater than 15 digits, including country code';
    const BAD_REQUEST_PAYMENT_CONTACT_ONLY_INDIAN_ALLOWED                       = 'Your payment was not successful as international phone number is not accepted by the seller. To pay successfully try using Indian phone number.';
    const BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED                        = 'Your payment was not successful as the seller does not accept selected card type.To pay successfully try using another method.';
    const BAD_REQUEST_PAYMENT_CARD_SUBTYPE_NOT_SUPPORTED                        = 'Corporate card is not allowed for this payment';
    const BAD_REQUEST_PAYMENT_CARD_SUBTYPE_CONSUMER_NOT_SUPPORTED               = 'Your payment was unsuccessful. Try using another card/method.';
    const BAD_REQUEST_PAYMENT_CARD_SUBTYPE_BUSINESS_NOT_SUPPORTED               = 'Your payment was unsuccessful. Try using another card/method.';
    const BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE                         = 'Your payment could not be completed due to insufficient account balance. Try another card or payment method.';
    const BAD_REQUEST_PAYMENT_CARD_DECLINED                                     = 'Card declined by bank';
    const BAD_REQUEST_PAYMENT_CARD_EXPIRED                                      = 'Card is expired';
    const BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE                          = 'Your payment was not successful as you have an invalid expiry date.To pay successfully try adding the right details';
    const BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID                              = 'Card details entered by the user are invalid.';
    const BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_PREVENTED_AUTHORIZATION         = 'Payment processing declined. The card issuing bank has prevented the payment from being authorized.';
    const BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE                        = 'You\'ve entered an incorrect card number. Try again."';
    const BAD_REQUEST_PAYMENT_CARD_NUMBER_POSSIBLY_INVALID                      = 'You\'ve entered an incorrect card number. Try again.';
    const BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_AMOUNT_LIMITS_EXCEEDED            = 'Payment processing failed because card\'s withdrawal amount limit has exceeded.';
    const BAD_REQUEST_PAYMENT_CARD_DAILY_WITHDRAWAL_FREQUENCY_LIMITS_EXCEEDED   = 'Payment processing failed because card\'s withdrawal frequency limit has exceeded.';
    const BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT          = 'The bank has declined the payment as this card cannot be used for this type of payment. Please use an alternate credit card for the purpose.';
    const BAD_REQUEST_PAYMENT_CARD_CVV_LENGTH_MUST_BE_THREE                     = 'The card cvv length should only be 3 digits';
    const BAD_REQUEST_PAYMENT_CARD_AMEX_CVV_LENGTH_MUST_BE_FOUR                 = 'The American Express card cvv length must be 4 digits';
    const BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED                    = 'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.';
    const BAD_REQUEST_PAYMENT_CARD_AUTHENTICATION_INVALID                       = 'Card authentication failed due to invalid response from gateway. Please retry or use another payment method';
    const BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED                      = 'Your payment was not successful as the seller does not support recurring payments.We suggest contacting the seller for more details.';
    const BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED                      = 'Recurring is not supported on this bank';
    const BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_TOKEN_MAX_AMOUNT              = 'Payment amount exceeds the maximum amount allowed.';
    const BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD                              = 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.';
    const BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED                    = 'Your payment could not be completed on time. Try again later.';
    const BAD_REQUEST_PAYMENT_FAILED_FEATURE_FORCE_TERMINAL_ID_NOT_ENABLED      = 'The feature force_terminal_id is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT                = 'Payment debit notification failed to deliver to customer';
    const BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_VERIFY_FAILED           = 'Payment debit notification failed to verify';
    const BAD_REQUEST_TOKEN_BASED_CARD_MANDATE                                  = 'The request failed as card mandate is token based.';
    const BAD_REQUEST_TOKEN_NOT_REPORTED_TO_MANDATE_HUB                         = 'The request failed as token is not reported to the mandate hub.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT                                         = 'Payment was not completed on time.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY                              = 'Payment was not completed on time.';
    const BAD_REQUEST_PAYMENT_TIMED_OUT_AT_WALLET_PAYMENT_PAGE                  = 'Payment was not completed on time.';
    const BAD_REQUEST_PAYMENT_UPI_REQUEST_TIMED_OUT                             = 'Payment failed because upi request timed out.';
    const BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED                              = 'Refund is currently not supported for this payment method';
    const BAD_REQUEST_PAYMENT_PARTIAL_REFUND_NOT_SUPPORTED                      = 'Partial refund is currently not supported for this payment method';
    const BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH                  = 'Capture amount must be equal to the amount authorized';
    const BAD_REQUEST_PAYMENT_CAPTURE_CURRENCY_MISMATCH                         = 'Capture request currency must be same as payment currency';
    const BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT                     = 'This bank is either not valid or is not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_NETBANKING_NOT_ENABLED_FOR_MERCHANT               = 'Netbanking not enabled for the merchant';
    const BAD_REQUEST_PAYMENT_INVALID_MOBILE                                    = 'Payment failed because of invalid mobile number';
    const BAD_REQUEST_PAYMENT_OLA_MONEY_ACCOUNT_DOES_NOT_EXIST_FOR_NUMBER       = 'Ola Money account does not exist for this number';
    const BAD_REQUEST_PAYMENT_INVALID_EMAIL                                     = 'Payment failed because of invalid email';
    const BAD_REQUEST_PAYMENT_WALLET_PER_DAY_LIMIT_EXCEEDED                     = 'Payment failed because daily limit of the wallet has exceeded';
    const BAD_REQUEST_PAYMENT_WALLET_PER_WEEK_LIMIT_EXCEEDED                    = 'Payment failed because weekly limit of the wallet has exceeded';
    const BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED                   = 'Payment failed because monthly limit of the wallet has been exceeded';
    const BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED                 = 'Payment amount for wallet is above the limit';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CARD                               = 'Card has been blocked by the wallet';
    const BAD_REQUEST_PAYMENT_METHOD_NOT_ALLOWED_FOR_ORDER                      = 'Payment method is not among the list of valid methods for order';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_MOBILE_NUMBER                      = 'Mobile number has been blocked by the wallet';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_IP_ADDRESS                         = 'Customer IP address has been blocked by the wallet';
    const BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER                           = 'Customer has been blocked by wallet';
    const BAD_REQUEST_PAYMENT_WALLET_MAX_WRONG_ATTEMPT_LIMIT_CROSSED            = 'Maximum wrong attempt limit crossed for wallet';
    const BAD_REQUEST_PAYMENT_WALLET_MAX_AMOUNT_LIMIT_CROSSED_FOR_CUSTOMER      = 'Maximum wallet payment amount limit has been crossed for the customer';
    const BAD_REQUEST_PAYMENT_WALLET_NOT_ACTIVATED                              = 'Wallet has not been activated for the customer';
    const BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST                        = 'Wallet user does not exist';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_MOBILE                     = 'Wallet account seems to be registered with invalid mobile number';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS                = 'No Wallet Account is associated with the given email and mobile number combination';
    const BAD_REQUEST_PAYMENT_WALLET_NO_USER_WITH_CELL                          = 'No Wallet Account is associated with specified contact number';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_ALREADY_EXIST_WITH_EMAIL           = 'Provided email is already associated with an existing wallet account';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_ALREADY_EXIST_WITH_CONTACT         = 'Provided contact is already associated with an existing wallet account';
    const BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INACTIVE                           = 'Wallet account associated is inactive.';
    const BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED                      = 'Payment failed due to wallet authentication failure';
    const BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE                       = 'Your payment could not be completed due to insufficient wallet balance. Try another payment method.';
    const BAD_REQUEST_WALLET_PAYOUT_INSUFFICIENT_BALANCE                        = 'Payout failed due to insufficient balance in wallet';
    const BAD_REQUEST_PAYMENT_WALLET_CONTACT_PAYUMONEY                          = 'Payment failed. Please contact care@payumoney.com using your registered email';
    const BAD_REQUEST_AIRTEL_MONEY_ACCOUNT_BLOCKED                              = 'Payment failed as airtel money account has been blocked. Please reset mPIN or call 400 for assistance';
    const BAD_REQUEST_AIRTEL_MONEY_RESET_MPIN                                   = 'Payment failed as airtel money mPIN has not been changed. Please call 121 to reset your mPIN.';
    const BAD_REQUEST_CONTACT_AIRTEL_MONEY_CUSTOMER_CARE_FOR_REFUND             = 'Refund failed. Please contact airtel money customer care.';
    const BAD_REQUEST_PAYMENT_TOPUP_INVALID_WALLET_TOKEN                        = 'Payment failed';
    const BAD_REQUEST_PAYMENT_WALLET_INVALID_GATEWAY_TOKEN                      = 'Payment failed';
    const BAD_REQUEST_PAYMENT_UPI_INVALID_VPA                                   = 'Invalid VPA. Please enter a valid Virtual Payment Address';
    const BAD_REQUEST_PAYMENT_UPI_INVALID_UPI_NUMBER                            = 'Invalid UPI Number. Please enter a valid UPI Number';
    const BAD_REQUEST_UNMAPPED_VPA                                              = 'This VPA is not mapped to any bank account.';
    const BAD_REQUEST_INVALID_P2P                                               = 'P2p fields are invalid.';
    const BAD_REQUEST_P2P_REGISTRATION_CARD_EXPIRED                             = 'Card used while setting UPI PIN has expired. Please use another debit card to reset UPI PIN or use another bank account for payment';
    const BAD_REQUEST_VPA_DOESNT_EXIST                                          = 'VPA does not exist.';
    const BAD_REQUEST_PAYMENT_UPI_APP_NOT_SUPPORTED                             = 'Your UPI application is facing issues with handling collect requests. Please try again later';
    const BAD_REQUEST_PAYMENT_UPI_APP_ONE_TIME_MANDATE_NOT_SUPPORTED            = 'Your UPI application does not support one time mandate.';
    const BAD_REQUEST_PAYMENT_UPI_FUNCTION_NOT_SUPPORTED                        = 'The requested UPI function is not supported';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_BLOCKED                           = 'UPI Collect is not allowed for your merchant category by NPCI. Please reach out to Razorpay support if you need any help.';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_MCC_AMOUNT_LIMIT_REACHED              = 'UPI Collect payment more than INR 5000 is not allowed on your merchant category by NPCI. Reach out to Razorpay support if you need any help';
    const BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH                             = 'Your payment amount is different from your order amount. To pay successfully, please try using right amount.';
    const BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE                 = 'Payment amount is greater than the amount due for order';
    const BAD_REQUEST_PAYMENT_UPI_MULTIPLE_ACCOUNTS_LINKED                      = 'Payment failed since account linked with multiple names';
    const BAD_REQUEST_UPI_INVALID_ATM_PIN                                       = 'Invalid PIN entered.';
    const BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY        = 'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.';
    const BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_LINKS          = 'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.';
    const BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_PAGES          = 'Your payment could not be completed as this business accepts domestic (Indian) card payments only. Try another payment method.';
    const BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_INVOICES               = 'International cards are not allowed for this merchant on invoices';
    const BAD_REQUEST_QR_RECEIVER_TYPE_IS_NOT_SUPPORTED                         = 'QR receiver type is not supported.';
    const BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED                                = 'This feature is not available for your account. Contact support to get it enabled';

    const BAD_REQUEST_MANDATE_EXECUTION_ATTEMPT_BEFORE_START_TIME               = 'Mandate execution attempted before the start time';

    const BAD_REQUEST_LOCKED_BALANCE_UPDATE_NON_BANKING                         = 'Locked Balance update is not allowed.';

    const BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE                             = 'Insufficient available balance to create the transaction.';
    const BAD_REQUEST_INSUFFICIENT_BALANCE_LOCKED                               = 'Insufficient available balance to make the transaction.';
    const BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MINIMUM_ALLOWED_AMOUNT           = 'Payment amount is lesser than the minimum amount allowed';
    const BAD_REQUEST_PAYMENT_ORDER_CURRENCY_MISMATCH                           = 'Payment currency provided does not match with the currency in order';
    const BAD_REQUEST_PAYMENT_LINK_CURRENCY_MISMATCH                            = 'Payment currency provided does not match with the currency in the payment page';
    const BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID                                = 'Payment already done for this order.';
    const BAD_REQUEST_REFUND_FAILED                                             = 'Refund failed';
    const BAD_REQUEST_SCROOGE_DASHBOARD_ERROR                                   = 'Scrooge dashboard error';
    const BAD_REQUEST_REFUND_NOT_ALLOWED                                        = 'Refunds cannot be created on your account.';
    const BAD_REQUEST_CARD_REFUND_NOT_ALLOWED                                   = 'Refunds cannot be created on your account for card payments';
    const BAD_REQUEST_PAYMENT_ALREADY_REFUNDED                                  = 'Refund failed';
    const BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE                                 = 'Your account does not have enough balance to carry out the refund operation. You can add funds to your account from your Razorpay dashboard or capture new payments.';
    const BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE_FALLBACK                        = 'Insufficient balance to issue refund. Please add refund credits through \'My Account\' section or capture new payments.';
    const BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS                                 = 'Your account does not have enough credits to carry out the refund operation.';
    const BAD_REQUEST_REFUND_PAYMENT_OLDER_THAN_SIX_MONTHS                      = 'Cannot issue refund since payment date is older than 6 months';
    const BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE                                 = 'Your account does not have enough balance to carry out the payout operation. You can add funds to your account from your Razorpay dashboard or capture new payments.';
    const BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING                         = 'Your account does not have enough balance to carry out the payout operation.';
    const BAD_REQUEST_PAYOUT_FAILED_UNKNOWN_ERROR                               = 'Payout failed. Contact support for help.';
    const BAD_REQUEST_PAYOUT_STATUS_UPDATE_ALLOWED_ONLY_IN_TEST_MODE            = 'Payout status update is allowed only in test mode';
    const BAD_REQUEST_FUND_ACCOUNT_ID_IS_REQUIRED                               = 'fund_account_id is required';
    const BAD_REQUEST_ONLY_INTERNAL_CONTACT_PERMITTED                           = 'Please send fund accounts of internal type contacts only';
    const BAD_REQUEST_APP_NOT_PERMITTED_TO_CREATE_PAYOUT_ON_THIS_CONTACT_TYPE   = 'App not allowed to create payout on this internal contact type';
    const BAD_REQUEST_REFUND_INVALID_STATE_TO_PROCESSED                         = 'Refund in an invalid state to be marked as processed';
    const BAD_REQUEST_REFUND_INVALID_STATE_UPDATE                               = 'Refund can not be updated to this state';
    const BAD_REQUEST_REFUND_NOT_SCROOGE                                        = 'Gateway refund cannot be called for non-scrooge gateway';
    const BAD_REQUEST_REFUND_ALREADY_PROCESSED                                  = 'Refund has already been processed';
    const BAD_REQUEST_ALL_FTA_NOT_FAILED                                        = 'All FTAs for the refund are not in failed state.';
    const BAD_REQUEST_REFUND_NOT_IN_CREATED                                     = 'Refund is not in created state';
    const BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD                                    = 'This operation is not allowed. Please contact Razorpay support for details.';
    const BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS                             = 'Merchant email already exists for account - ';
    const BAD_REQUEST_EMAIL_TO_BE_REMOVED_NOT_PRESENT                           = 'Merchant email to be removed does not exist';
    const BAD_REQUEST_DOMAIN_ALREADY_WHITELISTED                                = 'Whitelisted domain to be added already exists for the merchant';
    const BAD_REQUEST_WHITELISTED_DOMAIN_NOT_FOUND                              = 'Whitelisted domain to be removed not found for the merchant';
    const BAD_REQUEST_MERCHANT_INVALID_MCC_CODE                                 = 'Invalid mcc code';
    const BAD_REQUEST_DUPLICATE_EXTERNAL_ID                                     = 'External Id already exists';
    const BAD_REQUEST_INVALID_POA_VERIFICATION_STATUS_CHANGE                    = 'Invalid poa verification status change ';
    const BAD_REQUEST_INVALID_BANK_DETAIL_VERIFICATION_STATUS_CHANGE            = 'Invalid bank detail verification status change';
    const BAD_REQUEST_ACCOUNT_REGISTRATION_ADDRESS_REQUIRED                     = 'Registered address is required';
    const BAD_REQUEST_MERCHANT_FUNDS_ALREADY_ON_HOLD                            = 'The merchant funds are already on hold';
    const BAD_REQUEST_MERCHANT_FUNDS_ALREADY_RELEASED                           = 'The merchant funds are already released';
    const BAD_REQUEST_RECEIPT_EMAILS_ALREADY_ENABLED                            = 'The merchant receipt emails are already enabled';
    const BAD_REQUEST_RECEIPT_EMAILS_ALREADY_DISABLED                           = 'The merchant receipt emails are already disabled';
    const BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED                             = 'Merchant international is already enabled';
    const BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED                            = 'Merchant international is already disabled';
    const BAD_REQUEST_INVALID_WORKFLOW_TYPE                                     = 'Merchant workflow type is not supported';
    const BAD_REQUEST_MERCHANT_INVALID                                          = 'The payment has been rejected by the gateway.';
    const BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED                 = 'Recurring payments are not supported for this merchant.';
    const BAD_REQUEST_MERCHANT_INVALID_RISK_ATTRIBUTE                           = 'Invalid merchant risk attribute provided.';
    const BAD_REQUEST_MERCHANT_RISK_ATTRIBUTES_REQUIRED                         = 'Require atleast one merchant risk attribute.';
    const BAD_REQUEST_MERCHANT_NO_DIFF_IN_RISK_ATTRIBUTES                       = 'No difference in the merchant risk attributes provided.';

    const BAD_REQUEST_ACTION_RISK_ATTRIBUTES_REQUIRED                           = 'Risk Attributes are required';
    const BAD_REQUEST_INVALID_ACTION_RISK_REASON                                = 'Invalid risk reason provided';
    const BAD_REQUEST_INVALID_ACTION_RISK_SOURCE                                = 'Invalid risk source provided';
    const BAD_REQUEST_INVALID_ACTION_RISK_TAG                                   = 'Invalid risk tag provided';
    const BAD_REQUEST_UNSUPPORTED_COMMUNICATION_TYPE                            = 'Communication type not yet supported';

    const BAD_REQUEST_PRODUCT_OFFER_AMOUNT_NOT_SUPPORTED                        = 'Offer Amount is not supported for this product';
    const BAD_REQUEST_AUTO_RECURRING_NOT_SUPPORTED_ON_IIN                       = 'Recurring payments are not supported on this iin';
    const BAD_REQUEST_TOKENISATION_FAILED_FOR_RECURRING_CARD                    = 'Failed to tokenised the card';
    const BAD_REQUEST_FAILED_REPORTING_TO_MANDATE_HUB                           = 'Failed while reporting to mandate hub';
    const BAD_REQUEST_UNABLE_TO_AUTHORIZE_PAYMENT                               = 'This payment could not be authorized by the processing bank.';
    const BAD_REQUEST_KEY_EXPIRED                                               = 'Key is expired';
    const BAD_REQUEST_KEY_EXPIRING_SOON                                         = 'Key is already set to expire soon';
    const BAD_REQUEST_KEY_OF_DEMO_ACCOUNT                                       = 'Operation failed for the key because it\'s of a demo account';
    const BAD_REQUEST_PAYMENT_CAPTURE_ONLY_AUTHORIZED                           = 'Only payments which have been authorized and not yet captured can be captured';
    const BAD_REQUEST_PAYMENT_CAPTURE_ONLY_PENDING                              = 'Only cash on delivery payments which are pending and not yet captured can be captured';
    const BAD_REQUEST_PAYMENT_CANCEL_ONLY_CREATED                               = 'Only payments which are just created can be cancelled';
    const BAD_REQUEST_NOTES_TOO_MANY_KEYS                                       = 'Number of fields in notes should be less than or equal to 15';
    const BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY                               = 'Notes values themselves should not be an array';
    const BAD_REQUEST_NOTES_KEY_TOO_LARGE                                       = 'Notes key cannot be greater 255 characters';
    const BAD_REQUEST_NOTES_VALUE_TOO_LARGE                                     = 'Notes value cannot be greater 512 characters';
    const BAD_REQUEST_NOTES_SHOULD_BE_ARRAY                                     = 'Notes should be provided as a dictionary';
    const BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED                           = 'Please provide your api key for authentication purposes.';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY                              = 'The api key provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET                           = 'The api secret provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID                           = 'The Account ID provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_KEY_TYPE                             = 'The key passed is not of a valid type';
    const BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED                                  = 'The partner is not allowed the requested access';
    const BAD_REQUEST_PARTNER_ACCOUNT_ID_REQUIRED                               = 'Account id is required with partner credentials';
    const BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER                                = 'The partner does not have access to the merchant';
    const BAD_REQUEST_UNAUTHORIZED_USER_ROLE_MISSING                            = 'Unauthorized Action';
    const BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED                          = 'Please provide secret for authentication';
    const BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE                  = 'Please do not provide your secret on public sided requests';
    const BAD_REQUEST_UNAUTHORIZED_API_KEY_NOT_PROVIDED                         = 'Please provide your Razorpay Api Key Id';
    const BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED                              = 'The api key provided by you has expired and cannot be used. Please use correct key and secret.';
    const BAD_REQUEST_UNAUTHORIZED                                              = 'Authentication failed';
    const BAD_REQUEST_UNAUTHORIZED_OAUTH_TOKEN_INVALID                          = 'The OAuth token used in the request was invalid or had expired';
    const BAD_REQUEST_UNAUTHORIZED_OAUTH_SCOPE_INVALID                          = 'The OAuth token used does not have sufficient permissions for this request';
    const BAD_REQUEST_UNAUTHORIZED_ACCESS_TO_RAZORPAYX_RESOURCE                 = 'The Application id does not have the permission to access RazorpayX resources';
    const BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED                 = 'The Merchant is not activated';
    const BAD_REQUEST_PRICING_ID_REQURED                                        = 'Pricing plan id is required';
    const BAD_REQUEST_PRICING_RATE_NOT_DEFINED                                  = 'One of percent_rate and fixed_rate must be present';
    const BAD_REQUEST_PRICING_GATEWAY_REQUIRED                                  = 'This plan has a gateway set. Please provide it in input';
    const BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED                              = 'The new rule matches with an active existing rule';
    const BAD_REQUEST_SAME_PRICING_RULE_ALREADY_EXISTS                          = 'The new rule is same as the previous rule';
    const BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS                        = 'Pricing plan name already exists. Please try another name';
    const BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT                          = 'The merchant does not have pricing assigned';
    const BAD_REQUEST_PRICING_FIELD_NOT_REQUIRED_FOR_NB                         = 'The field should be null for net-banking';
    const BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT                         = 'Amex pricing rule not present for merchant';
    const BAD_REQUEST_ANOTHER_PRICING_UPDATE_IN_PROGRESS                        = 'Request failed because another merchant pricing update is in progress';
    const BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES                   = 'Pricing Plan cannot have rules of multiple types';
    const BAD_REQUEST_BUY_PRICING_RANGE_VALIDATION_FAILED                       = 'Rules Not Defined On Complete Range For MAI';
    const BAD_REQUEST_PRICING_TYPE_COMMISSION_INVALID_FOR_NON_RZP_ORG           = 'Commission plan cannot be posted for this org';
    const BAD_REQUEST_MULTIPLE_PLAN_NAME_ON_SAME_GATEWAY_MERCHANT_ID            = 'Multiple Plans sent for same Gateway Merchant Id terminals';
    const BAD_REQUEST_PRICING_TYPE_BUY_PRICING_INVALID_FOR_NON_RZP_ORG          = 'Buy Pricing plan cannot be assigned for this org';
    const BAD_REQUEST_PRICING_RULE_FOR_CARD_NETWORK_NOT_PRESENT                 = 'Pricing rule not present for merchant with this card network';
    const BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP                     = 'Pricing rule amount range collides with another existing rule\'s amount range.';
    const BAD_REQUEST_UNKNOWN_SCHEDULE                                          = 'Schedule not found in database.';
    const BAD_REQUEST_INVALID_SCHEDULE                                          = 'Schedule cannot be created, it is invalid.';
    const BAD_REQUEST_SCHEDULE_REQUIRED                                         = 'Mandatory param: Schedule not given.';
    const BAD_REQUEST_SCHEDULE_INVALID_PERIOD                                   = 'Invalid period, must be among hourly, daily, weekly, monthly-date, and monthly-week';
    const BAD_REQUEST_SCHEDULE_INVALID_TYPE                                     = 'Invalid type, must be among settlement, subscription';
    const BAD_REQUEST_SCHEDULE_ANCHOR_NOT_PERMITTED                             = 'Setting anchor is not permitted for the schedule.';
    const BAD_REQUEST_SCHEDULE_HOURLY_HOUR_NOT_PERMITTED                        = 'Setting hour is not permitted for hourly schedules';
    const BAD_REQUEST_SCHEDULE_HOURLY_WITHOUT_INTERVAL                          = 'Hourly schedules require an interval to be set.';
    const BAD_REQUEST_SCHEDULE_IN_USE                                           = 'Cannot delete a schedule that is currently in use by one or more merchants.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ACCOUNT                    = 'Creation of new virtual accounts is currently blocked for your account.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER                      = 'Creation of new virtual accounts is currently blocked for this order.';
    const BAD_REQUEST_QR_CODE_DISALLOWED_FOR_ORDER                              = 'Creation of new QR Code is currently blocked for this order.';
    const BAD_REQUEST_QR_CODE_REF_ID_GENERATION_FAILURE                         = 'QrCode creation failed due to error at bank or wallet gateway';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES                    = 'One or more of the given receiver types is invalid.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_IDENTICAL_DESCRIPTOR                      = 'An active virtual account with the same descriptor already exists for your account.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH                 = 'Invalid length for descriptor.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_UNAVAILABLE                               = 'A virtual account with this descriptor is unavailable at this time.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS                     = 'Request failed because another virtual account operation is in progress';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_RECEIVER_ALREADY_PRESENT                  = 'Receiver type is already present for the virtual account';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_EXPIRY_DATE                       = 'Expiry Date is not a valid date.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_EXPIRY_LESS_THAN_CURRENT_TIME             = 'Expiry Date cannot be less than system date.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_RECEIVER_WITH_ORDER                   = 'Can\'t add receiver to existing VA with order';

    const BAD_REQUEST_BANK_TRANSFER_FEE_CALCULATED_GREATER_THAN_PAYMENT_AMOUNT  = 'Fee calculated is greater than the payment amount.';

    const BAD_REQUEST_IIN_NOT_EXISTS                                            = 'IIN does not exist';
    const BAD_REQUEST_INVALID_TOKEN_IIN                                         = 'The requested IIN is not a valid token IIN';
    const BAD_REQUEST_CORPORATE_CARD_INVALID_EXPIRY_DATE                        = 'Expiry date is not valid';
    const BAD_REQUEST_INVALID_IIN                                               = 'The requested IIN is a token IIN & should be 9 digits long.';
    const BAD_REQUEST_EDIT_TRANSACTION_LIMIT_REQUEST_MADE_IN_LAST_30_DAYS       = 'Bad Request Edit Transaction Limit Request Made In Last 30 Days';
    const BAD_REQUEST_EDIT_TRANSACTION_LIMIT_CTS_OR_FTS_MORE_THAN_5             = 'The transaction limit cannot be updated for your account. Please reach out to our support team for further clarification';

    const BAD_REQUEST_NON_3DS_INTERNATIONAL_NOT_ALLOWED                         = '3dsecure is not enabled for the card by the cardholder or the bank/issuer';

    // Virtual VPA Prefix
    const BAD_REQUEST_VIRTUAL_VPA_PREFIX_UNAVAILABLE                            = 'This prefix is already in use. Please try another prefix.';
    const BAD_REQUEST_VIRTUAL_VPA_PREFIX_NOT_ALLOWED                            = 'Prefix is not enabled for this merchant.';

    const BAD_REQUEST_BUSINESS_INFRINGEMENT_PHRASES                             = 'Business infringement phrases have been identified, please review the text in the field : ';
    const BAD_REQUEST_ACCOUNT_CLOSED                                            = 'Bank Account is closed.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED                                    = 'Virtual Account is closed';
    const BAD_REQUEST_ACCOUNT_NUMBER_MISMATCH                                   = 'Bank Account Number does not match.';
    const BAD_REQUEST_ACCOUNT_BLOCKED                                           = 'Bank Account is blocked.';
    const BAD_REQUEST_ACCOUNT_DORMANT                                           = 'Bank account is dormant.';
    const BAD_REQUEST_NO_DR_ALLOWED                                             = 'Debit is not allowed on the bank account.';
    const BAD_REQUEST_TRANSACTION_AMOUT_GREATER_THAN_REGISTERED_AMOUNT          = 'Transaction amount exceeds the allowed amount.';
    const BAD_REQUEST_FREQUENCY_DEBIT_LIMIT_EXCEEDED                            = 'The frequency of debit on the account has been exceeded.';

    const BAD_REQUEST_RAZORPAYX_ACCOUNT_NUMBER_IS_INVALID                       = 'The RazorpayX Account number is invalid.';

    const BAD_REQUEST_ACTIVE_PROMOTION_FOR_EVENT_ALREADY_EXISTS                 = 'A promotion for given event already exists';

    const BAD_REQUEST_MERCHANT_ACTIVATION_FORM_NOT_SUBMITTED                    = 'The merchant has not submitted the activation form yet.';
    const BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED                                = 'The merchant has already been activated';
    const BAD_REQUEST_MERCHANT_NOT_ACTIVATED                                    = 'The merchant has not been activated. This action can only be taken for activated merchants';
    const BAD_REQUEST_PARTNER_FORM_UNDER_NEEDS_CLARIFICATION                    = 'Cannot save merchant form details since partner form is under needs clarification';
    const BAD_REQUEST_INVALID_SUBCATEGORY                                       = 'The business subcategory is not valid';
    const BAD_REQUEST_INVALID_VERIFICATION_TYPE                                 = 'The verification type is not valid';
    const BAD_REQUEST_MERCHANT_UNARCHIVE_BEFORE_ACTIVATION                      = 'The merchant must be unarchived before being activated.';
    const BAD_REQUEST_MERCHANT_CANNOT_BE_ARCHIVED                               = 'The merchant cannot be archived';
    const BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED                                 = 'The merchant has already been archived.';
    const BAD_REQUEST_MERCHANT_EMAIL_AND_INPUT_EMAIL_DIFFERENT                  = 'Merchant email and input email are different';
    const BAD_REQUEST_MERCHANT_USER_NOT_PRESENT                                 = 'Merchant has no user to send Activation SMS';
    const BAD_REQUEST_MERCHANT_NOT_ARCHIVED                                     = 'The merchant has not been archived. This action can only be taken for archived merchants';
    const BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED                                = 'The merchant has already been suspended';
    const BAD_REQUEST_MERCHANT_NOT_SUSPENDED                                    = 'The merchant has not been suspended. This action can only be taken for suspended merchants';
    const BAD_REQUEST_MERCHANT_SUSPENDED                                        = 'The merchant has been suspended. The action is invalid';
    const BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED                             = 'The input action is not supported for the merchant';
    const BAD_REQUEST_BATCH_ACTION_NOT_SUPPORTED                                = 'The input batch action is not supported for the merchant';
    const BAD_REQUEST_BATCH_ACTION_ENTITY_NOT_SUPPORTED                         = 'The input batch action entity is not supported for the merchant';
    const BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS                           = 'Merchant details does not exists';
    const BAD_REQUEST_MERCHANT_DETAIL_CANNOT_BE_UPDATED                         = 'Merchant details cannot be updated';
    const BAD_REQUEST_CANNOT_UPDATE_COMMON_FIELDS                               = 'Fields already submitted in partner activation form';
    const BAD_REQUEST_MERCHANT_ALREADY_LIVE                                     = 'The merchant is already live';
    const BAD_REQUEST_MERCHANT_NOT_LIVE                                         = 'The merchant is not live currently';
    const BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED                           = 'There is a temporary block placed on the account currently because of which new payment operations are put on hold. If you are seeing this message unexpectedly, please contact the site admin regarding the issue.';
    const BAD_REQUEST_MERCHANT_ALREADY_INTERNATIONAL                            = 'The merchant already has international activated';
    const BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED                        = 'The merchant doesn\'t have international activated';
    const BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED                             = 'The merchant has no pricing assigned';
    const BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED                              = 'The merchant keys have already been created';
    const BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED                  = 'The merchant keys cannot be created since account is not activated yet.';
    const BAD_REQUEST_MERCHANT_NO_KEY_ACCESS                                    = 'You are not allowed to perform this operation';
    const BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND                            = 'The merchant has not yet provided his bank account details';
    const BAD_REQUEST_PARTNER_NO_BANK_ACCOUNT_FOUND                             = 'The Partner has not yet provided his bank account details';
    const BAD_REQUEST_MERCHANT_BANK_ACCOUNT_ALREADY_PROVIDED                    = 'The merchant already has provided a bank account';
    const BAD_REQUEST_MERCHANT_BANK_ACCOUNT_UPDATE_IN_PROGRESS                  = 'A previous request to update your bank account is in progress. Please try after some time';
    const BAD_REQUEST_MERCHANT_BANK_ACCOUNT_UPDATE_LAXMI_VILAS_BANK_PROHIBITED  = 'We are unable to complete this operation due to the restrictions on Laxmi Vilas Bank\'s operations by RBI (Gazette notification (S.O. 4127(E)) dated 17th November 2020';
    const BAD_REQUEST_CANNOT_ADD_SUBMERCHANT                                    = 'The merchant cannot add a sub-merchant';
    const BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED                     = 'The merchant cannot add a sub-merchant without providing email';
    const BAD_REQUEST_GATEWAY_TERMINAL_MAX_LIMIT_REACHED                        = 'Max terminal count limit reached for this merchant';
    const BAD_REQUEST_GATEWAY_MERCHANT_ID_EXISTS                                = 'A record with same gateway merchant id (mid) exists';
    const BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY                      = 'A terminal for this gateway for this merchant already exists';
    const BAD_REQUEST_MERCHANT_TXN_LIMIT_EXCEEDED                               = 'Amount exceeds total payment limit threshold';
    const BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT                    = 'This operation is not allowed for test accounts';
    const BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS                   = 'A terminal with the same field exists - ';
    const BAD_REQUEST_TERMINAL_STATUS_SHOULD_BE_ACTIVATED_OR_PENDING_TO_ENABLE  = 'Terminal status should be activated or pending for enabling a terminal';
    const BAD_REQUEST_REFUND_ALREADY_IN_UNPROCESSED_LIST                        = 'The refund is already present in the unprocessed list in cache';
    const BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET                                  = 'Business website is invalid or not set';
    const BAD_REQUEST_CARD_PAYMENT_DECLINED_MODE_NOT_SUPPORTED                  = 'Payment declined by issuer. Issuer does not support this mode of transaction.';
    const BAD_REQUEST_CARDHOLDER_STOPPED_WITHDRAWALS                            = 'Payments Blocked by cardholder on this card.';
    const BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED                   = 'Payment processing failed because card\'s withdrawal amount limit has exceeded.';
    const BAD_REQUEST_MERCHANT_EMAIL_DOES_NOT_EXIST                             = 'Merchant email type does not Exist';
    const BAD_REQUEST_MERCHANT_REFERRAL_DOES_NOT_EXIST                          = 'Merchant referral does not Exist';
    const BAD_REQUEST_INVALID_INTERNATIONAL_STATUS_CHANGE_REQUEST               = 'Merchant Can not Enable/Disable International';
    const INVALID_ARGUMENT_INVALID_INTERNATIONAL_ACTIVATION_FLOW                = 'Server error while performing operation';
    const BAD_REQUEST_NOT_SUPPORTED_FEATURE                                     = 'Server error while performing operation';
    const BAD_REQUEST_PAYMENT_INVALID_MERCHANT_NAME                             = 'Invalid merchant name sent to the gateway';
    const BAD_REQUEST_GSTIN_SELF_SERVE_IN_PROGRESS                              = 'A previous request to update your gstin is already in progress';
    const BAD_REQUEST_PARTNER_REFERRAL_DOES_NOT_EXIST                           = 'Partner referral does not exist';

    // Debit EMI errors
    const BAD_REQUEST_DEBIT_EMI_CUSTOMER_NOT_ELIGIBLE                           = 'Debit Card EMI offer is not available for the entered details';

    // HDFC DC EMI errors
    const BAD_REQUEST_HDFC_DEBIT_EMI_CUSTOMER_NOT_ELIGIBLE                      = 'Your payment was declined as you are not eligible with the provider. To pay successfully try using another method.';
    const BAD_REQUEST_DEBIT_EMI_HDFC_MAXIMUM_AMOUNT_LIMIT                       = 'Transaction Value is greater than pre-approved limit. To check your Debit Card Pre approved limit send SMS, MYHDFC to 5676712';

    //on_demand
    const BAD_REQUEST_INSUFFICIENT_BALANCE                                      = 'Amount requested for the ondemand settlement exceeds the settlement balance.';
    const BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED             = 'Amount requested is more than the max limit for ondemand settlement';
    const BAD_REQUEST_AMOUNT_LESS_THAN_MIN_LIMIT_FOR_NON_ES_AUTOMATIC_MERCHANTS = 'Minimum amount that can be settled is ₹ 2000.';
    const BAD_REQUEST_AMOUNT_LESS_THAN_MIN_ONDEMAND_AMOUNT                      = 'Minimum amount that can be settled is ₹ 1.';

    const BAD_REQUEST_DOCUMENT_TYPE_INVALID                                     = 'invalid document type';
    const BAD_REQUEST_PROOF_TYPE_INVALID                                        = 'invalid proof type';
    const BAD_REQUEST_PROOF_TYPE_NOT_SUPPORTED                                  = 'proof type not supported';
    const BAD_REQUEST_DOCUMENT_UPLOAD_PURPOSE_INVALID                           = 'invalid document upload purpose';
    const INVALID_ARGUMENT_INVALID_FILE_HANDLER_SOURCE                          = 'Server error';

    const BAD_REQUEST_INVALID_STATE_CODE                                        = 'invalid state code';

    const BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_PENDING                       = 'Payment processing pending';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED                       = 'Payment failed because UPI request expired';
    const BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED                      = 'Payment rejected by customer';

    const SERVER_ERROR_UPI_TRANSFER_PROCESSING_FAILED                           = 'Upi transfer processing failed';
    const SERVER_ERROR_QR_PAYMENT_PROCESSING_FAILED                             = 'Qr payment processing failed';

    const BAD_REQUEST_CARD_INVALID_DATA                                         = 'Payment failed as card details (CVV or expiry date) are incorrect. Please check and try again';
    const BAD_REQUEST_CASHBACK_EXCEEDS_ISSUER_LIMIT                             = 'Cashback request exceeds issuer limit';
    const BAD_REQUEST_PAYMENT_VERIFICATION_FAILED                               = 'Payment verification with gateway failed';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_CLICKING_CANCEL                       = 'Payment declined by gateway. Most probably due to customer clicking the cancel button on 3dSecure page';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY                               = 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_BANK                                  = 'Payment declined by bank';
    const BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK                    = 'Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.';
    const BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR                                 = 'Payment failed due to error in the bank system';
    const BAD_REQUEST_PAYMENT_FAILED_MAYBE_DUE_TO_INVALID_INPUT                 = 'Payment processing failed most probably due to invalid card input';
    const BAD_REQUEST_PAYMENT_FAILED_MISSING_ORDER_ID                           = 'Payment processing failed due to missing order id';
    const BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN                         = 'Your payment didn\'t go through due to invalid card details. Try another payment method or contact your bank for details.';
    const BAD_REQUEST_PAYMENT_CANCELLED                                         = 'Payment processing cancelled';
    const BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK                     = 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.';
    const BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER                      = 'Payment processing via netbanking cancelled by user by clicking cancel on bank transfer page';
    const BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED                     = 'Your payment could not be completed due to incorrect OTP or verification details. Try another payment method or contact your bank for details.';
    const BAD_REQUEST_PAYMENT_AMEX_3DSECURE_AUTH_FAILED                         = 'The card is not enrolled for American Express SafeKey program. Please try another card.';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK                      = 'Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY_DUE_TO_RISK                   = 'Payment processing failed by gateway due to risk';
    const BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_BLOCKED_CARD              = 'Payment processing failed because cardholder\'s card was blocked';
    const BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE                    = 'Payment processing failed because cardholder is not enrolled for the required 3dsecure authentication';
    const BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED             = 'Payment processing failed because OTP validation attempts limit exceeded';
    const BAD_REQUEST_PAYMENT_OTP_ATTEMPT_LIMIT_EXCEEDED                        = 'Payment processing failed because OTP validation attempts limit exceeded';
    const BAD_REQUEST_PAYMENT_OTP_INCORRECT                                     = 'Payment processing failed because of incorrect OTP';
    const BAD_REQUEST_PAYMENT_OTP_INCORRECT_OR_EXPIRED                          = 'Payment processing failed because of incorrect or expired OTP';
    const BAD_REQUEST_PAYMENT_OTP_EXPIRED                                       = 'Payment processing failed because of expired OTP';
    const BAD_REQUEST_PAYMENT_PARES_XML_SIGNATURE_ERROR                         = 'Payment processing failed because of card authentication failure';
    const BAD_REQUEST_PAYMENT_ABORTED                                           = 'Payment processing aborted';
    const BAD_REQUEST_PAYMENT_MISSING_DATA                                      = 'One or more required fields are missing';
    const BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_REFUNDED                      = 'Total amount passed is more than the Return/Void amount.';
    const BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN                     = 'Payment declined by card issuing bank as card may be inactive or not allowed for online payments. Please use another card or contact bank.';
    const BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID                                 = 'Invalid card type.';
    const BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY                        = 'Invalid amount or currency.';
    const BAD_REQUEST_PAYMENT_INVALID_CAPTURE                                   = 'No approved preauth transaction was found.';
    const BAD_REQUEST_PAYMENT_INVALID_FORMAT                                    = 'Payment Failed.';
    const BAD_REQUEST_PAYMENT_INVALID_STATUS                                    = 'Payment status is not valid for the operation';
    const BAD_REQUEST_PAYMENT_INVALID_TRANSACTION_DATE                          = 'Invalid transaction date.';
    const BAD_REQUEST_PAYMENT_MAX_TRANSACTIONS_PER_ORDER_EXCEEDED               = 'The maximum number of transactions per order has been exceeded';
    const BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED                             = 'Max number of PIN retries exceeded.';
    const BAD_REQUEST_PAYMENT_PIN_INCORRECT                                     = 'Incorrect Pin';
    const BAD_REQUEST_PAYMENT_TERMINAL_STATE_CODE_EXCEEDED_LENGTH               = 'This transaction is not permitted by gateway.';
    const BAD_REQUEST_PAYMENT_TXN_NOT_PUSHED_TO_NET_BANKING                     = 'Transaction was not posted to Net Banking.';
    const BAD_REQUEST_PAYMENT_TXN_REJECTED_FROM_NET_BANKING                     = 'Transaction was not posted to Net Banking.';
    const BAD_REQUEST_PAYMENT_VOID_NOT_SUPPORTED                                = 'Void is not supported for refund transaction on this endpoint.';
    const BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA                           = 'Payment failed because of invalid account withdrawer';
    const BAD_REQUEST_INVALID_PARAMETERS                                        = 'One or more fields have invalid data';
    const BAD_REQUEST_PAYMENT_PARTIAL_AMOUNT_APPROVED                           = 'Payment declined because partial amount was approved';
    const BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT  = 'Recurring payments on international cards not supported for merchant.';
    const BAD_REQUEST_PAYMENT_ALREADY_ACKNOWLEDGED                              = 'Payment has already been acknowledged.';
    const BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED                                = 'Payment failed';
    const BAD_REQUEST_PURCHASE_ERROR                                            = 'Payment failed';
    const BAD_REQUEST_MAX_DEFERRED_PAYMENT_REACHED                              = 'Payment failed';
    const BAD_REQUEST_ORDER_DOES_NOT_EXIST                                      = 'Order does not exist.';
    const BAD_REQUEST_ORDER_EXISTS                                              = 'Payment failed';
    const BAD_REQUEST_ORDER_INVALID_OFFER                                       = 'Offer applied not valid for order';
    const BAD_REQUEST_ORDER_MULTIPLE_OFFERS                                     = 'Multiple offers cannot be applied on a single order.';
    const BAD_REQUEST_ORDER_CURRENCY_NOT_SUPPORTED                              = 'Currency is not supported';
    const BAD_REQUEST_ORDER_ANOTHER_OPERATION_IN_PROGRESS                       = 'Request failed because another order operation is in progress';
    const BAD_REQUEST_ORDER_BANK_NOT_ENABLED_FOR_MERCHANT                       = 'The requested bank is not enabled for the merchant';
    const BAD_REQUEST_UNSUPPORTED_CHARACTER_SET                                 = 'Error occurred because of invalid data';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_HASH                                 = 'Hash Data is invalid.';
    const BAD_REQUEST_BATCH_UPLOAD_INVALID_TOKEN                                = 'Token expired or invalid';
    const BAD_REQUEST_CORPORATE_CARD_INVALID_TOKEN                              = 'Token expired or invalid';
    const BAD_REQUEST_EMANDATE_TOKEN_PASSED_IN_FIRST_RECURRING                  = 'Token should not be passed in first E-mandate recurring payment';
    const BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING              = 'Token is not confirmed for recurring payments';
    const BAD_REQUEST_NON_ACTIVATED_TOKEN_PASSED_IN_RECURRING                   = 'Token is not activated for recurring payments';
    const BAD_REQUEST_TOKEN_STATUS_ALREADY_PAID                                 = 'Payment has already been done for this token';
    const BAD_REQUEST_VALIDATION_FAILURE                                        = 'Something went wrong, please try again after sometime.';
    const BAD_REQUEST_INPUT_VALIDATION_FAILURE                                  = 'Something went wrong, please try again after sometime.';
    const BAD_REQUEST_SIGNATURE_ERROR                                           = 'There is something wrong with the signatures in the request';
    const BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN                         = 'Payment failed because account withdrawal are frozen';
    const BAD_REQUEST_PAYMENT_INVALID_ACCOUNT                                   = 'Payment failed because of invalid account';
    const BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION                = 'E-Mandate registration cancelled by the customer';
    const BAD_REQUEST_EMANDATE_REGISTRATION_FAILED_JOINT_ACCOUNT                = 'Mandate registration is not allowed for Joint Account';
    const BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME                               = 'The account holder name is invalid';
    const BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER                             = 'Payment cancelled by customer';
    const BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED                        = 'Payment failed because account limit exceeded';
    const BAD_REQUEST_PAYMENT_KYC_PENDING                                       = 'Payment failed because account KYC pending';
    const BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED                            = 'User is not registered for NetBanking payments.';

    const BAD_REQUEST_AUTH_LINK_EMAIL_EMPTY                                     = 'The email field is required for recurring links';
    const BAD_REQUEST_AUTH_LINK_CONTACT_EMPTY                                   = 'The contact field is required for recurring links';
    const BAD_REQUEST_EMANDATE_AMOUNT_LIMIT_EXCEEDED                            = 'Amount exceeds E-mandate limit';
    const BAD_REQUEST_EMANDATE_REGISTRATION_FAILED                              = 'E-mandate registration failed';
    const BAD_REQUEST_NACH_REGISTRATION_FAILED                                  = 'Nach registration failed';
    const BAD_REQUEST_EMANDATE_INVALID_AADHAAR_BANK_ACCOUNT_MAPPING             = 'EMandate registration failed because of invalid aadhaar bank account mapping';
    const BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED                               = 'EMandate aadhaar not mapped';
    const BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE                               = 'Payment failed because emandate is cancelled or inactive';
    const BAD_REQUEST_INVALID_IFSC_CODE                                         = 'The IFSC Code is invalid';
    const BAD_REQUEST_EMANDATE_FREQUENCY_MISMATCH                               = 'Debit request is not as per mandate frequency';
    const BAD_REQUEST_EMANDATE_EXPIRED                                          = 'The mandate has expired';
    const BAD_REQUEST_EMANDATE_DEBIT_NOT_ALLOWED                                = 'Debiting the customer account is not allowed for this mandate';
    const GATEWAY_ERROR_DEBIT_BEFORE_MANDATE_START                              = 'Debit request is before emandate start date';
    const GATEWAY_ERROR_DEBIT_AFTER_MANDATE_END                                 = 'Debit request is after emandate end date';
    const BAD_REQUEST_MANDATE_USER_MISMATCH                                     = 'User ID does not match with the registered ID';
    const BAD_REQUEST_EMANDATE_REGISTRATION_ACTION_NEEDED                       = 'Customer should refer to the branch to enable the mandate';
    const BAD_REQUEST_EMANDATE_SETTLEMENT_FAILED                                = 'Unable to settle funds for this payment';
    const BAD_REQUEST_EMANDATE_DEBIT_TIME_BREACHED                              = 'Debit request initiated outside business hours';
    const BAD_REQUEST_INVALID_UMRN                                              = 'Invalid UMRN for token migration';

    const BAD_REQUEST_GATEWAY_REFUND_ABSENT                                     = 'Refund not done on the gateway side.';
    const BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED                            = 'Void is not supported by the gateway';
    const BAD_REQUEST_REFUND_PARTIAL_VOID_NOT_SUPPORTED                         = 'Void is not supported for partial refunds';
    const BAD_REQUEST_INVALID_GATEWAY                                           = 'Invalid gateway provided for the request';
    const BAD_REQUEST_PAYMENT_SUBSCRIPTION_NOT_RECURRING                        = 'Recurring is not set for the subscription payment';
    const BAD_REQUEST_SUBSCRIPTION_TOKEN_ALREADY_ASSOCIATED                     = 'The subscription already has a token associated with it';
    const BAD_REQUEST_SUBSCRIPTION_TOKEN_NOT_ASSOCIATED                         = 'Payment cannot be authorized since subscription does not have any token associated';
    const BAD_REQUEST_SUBSCRIPTION_TOTAL_COUNT_EXCEEDED                         = 'Subscription has already reached its total count of charges';
    const BAD_REQUEST_SUBSCRIPTION_IN_TERMINAL_STATE                            = 'The subscription is in a terminal state';
    const BAD_REQUEST_SUBSCRIPTION_NOT_IN_ACTIVE_OR_HALTED_STATE                = 'The subscription should be in either active or halted state to charge an on_hold invoice';
    const BAD_REQUEST_SUBSCRIPTION_NOT_IN_PENDING_STATE                         = 'The subscription is not in pending state, and cannot be retried.';
    const BAD_REQUEST_FUND_ACCOUNT_DOESNT_BELONG_TO_INTENDED_CONTACT            = 'Passed fund account id belongs to another contact.';
    const BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE                             = 'This operation is not allowed in live mode.';
    const BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST                       = 'Cannot proceed without X-Dashboard-User-Id header in the request.';
    const BAD_REQUEST_SUBSCRIPTION_NOT_TEST_CHARGEABLE                          = 'The subscription is not test chargeable.';
    const BAD_REQUEST_SUBSCRIPTION_INVOICE_CANNOT_BE_CHARGED                    = 'This invoice of the subscription cannot be charged.';
    const BAD_REQUEST_SUBSCRIPTION_2FA_NOT_ALLOWED                              = 'Customer payment not allowed for the subscription at this stage.';
    const BAD_REQUEST_SUBSCRIPTION_CARD_CHANGE_NOT_ALLOWED                      = 'Cannot change card for the subscription at this state';
    const BAD_REQUEST_SUBSCRIPTION_CUSTOMER_NOT_FOUND                           = 'Could not find the customer for the subscription';
    const BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT                    = 'customer_id should not be sent in the input for subscription payment';
    const BAD_REQUEST_INVALID_CHARGE_ACCOUNT                                    = 'Invalid charge account';
    const BAD_REQUEST_SUBSCRIPTION_SAVE_CARD_DISABLED                           = 'Subscription payment cannot be made with Flash Checkout disabled';
    const BAD_REQUEST_SUBSCRIPTION_PAYMENT_WITHOUT_SAVING                       = 'Subscription payment cannot be made without saving the card';
    const BAD_REQUEST_SUBSCRIPTION_ANOTHER_OPERATION_IN_PROGRESS                = 'Request failed because another subscription operation is in progress';

    const BAD_REQUEST_SUBSCRIPTION_INVALID_STATUS                               = 'Invalid status passed in the query params';
    const BAD_REQUEST_SUBSCRIPTION_SCHEDULED_FAILURE                            = 'Subscription charge underwent an expected failure.';
    const BAD_REQUEST_SUBSCRIPTION_CYCLE_NOT_RUNNING                            = 'Subscription cannot be cancelled since no billing cycle is going on';
    const BAD_REQUEST_SUBSCRIPTION_LAST_CYCLE_CANNOT_CANCEL                     = 'Subscription cannot be cancelled at cycle end since the last cycle is going on already.';
    const BAD_REQUEST_ADDON_DELETE_NOT_ALLOWED                                  = 'Delete operation cannot be performed on the addon.';
    const BAD_REQUEST_SUBSCRIPTION_PAYMENT_PARAMS_MISSING                       = 'One or more parameters missing for creating the subscription payment.';
    const BAD_REQUEST_INVOICE_CHARGE_FAILED                                     = 'Manual attempt of payment on this subscription invoice has failed';

    const BAD_REQUEST_INVOICE_STATUS_UNAVAILABLE                                = 'Invoice status cannot be retrieved now';
    const BAD_REQUEST_PAYMENT_NOT_AUTHORIZED                                    = 'Payment is not in authorized state';
    const BAD_REQUEST_NOT_CARD_PAYMENT                                          = 'Payment was not done using card';
    const BAD_REQUEST_INVALID_MESSAGE_KEYWORD                                   = 'Invalid keyword sent in the request';
    const BAD_REQUEST_MISSING_FIELDS_MESSAGE                                    = 'Some fields are missing in the request';
    const BAD_REQUEST_CUSTOMER_ID_MISSING                                       = 'Sending customer ID is mandatory';
    const BAD_REQUEST_BANK_ACCOUNT_ID_MISSING                                   = 'Sending bank account id is mandatory';
    const BAD_REQUEST_DUPLICATE_VPA                                             = 'Duplicate VPA address, try a different username.';
    const BAD_REQUEST_END_AT_AND_TOTAL_COUNT_SENT                               = 'Either end_at or total_count should be sent and not both.';
    const BAD_REQUEST_INVALID_TRANSACTION_AMOUNT                                = 'The amount does not match with the expected amount for the transaction. It might have been tampered.';
    const BAD_REQUEST_SUBSCRIPTION_CURRENT_TIME_PAST_START_TIME                 = 'Subscription\'s start time is past the current time. Cannot do an auth transaction now.';

    const BAD_REQUEST_SUBSCRIPTION_ALREADY_AUTHENTICATED                        = 'The subscription has already been authenticated.';
    const BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD                                    = 'Payment declined because it didn\'t pass all risk checks';
    const BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD_WEBSITE_MISMATCH                   = 'Payment blocked as website does not match registered website(s)';
    const BAD_REQUEST_CARD_AVS_FAILED                                           = 'Payment processing failed because address validation failed';
    const BAD_REQUEST_CARD_STOLEN_OR_LOST                                       = 'Your payment could not be completed as your card is blocked. Try another payment method or contact your bank for details. ';
    const BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE                             = 'Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.';
    const BAD_REQUEST_CARD_INACTIVE                                             = 'Payment failed because given card is inactive';
    const BAD_REQUEST_CARD_CREDIT_LIMIT_REACHED                                 = 'Payment failed because card has reached it\'s limit';
    const BAD_REQUEST_CARD_FROZEN                                               = 'Payment failed because given card is inactive';
    const BAD_REQUEST_CARD_DAILY_LIMIT_REACHED                                  = 'Payment failed because card has reached it\'s limit';
    const BAD_REQUEST_CARD_BILL_SHIP_MISMATCH                                   = 'Payment failed';

    const BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED                                 = 'Order id is mandatory for payment';
    const BAD_REQUEST_ORDER_METHOD_REQUIRED_FOR_MERCHANT                        = 'Order payment method is mandatory for this merchant';
    const BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT                = 'Account number is mandatory for this merchant';
    const BAD_REQUEST_ORDER_ACCOUNT_NUMBER_INCORRECT_LENGTH                     = 'Account number is of incorrect length for this bank.';
    const BAD_REQUEST_ORDER_BANK_INVALID                                        = 'Bank code provided is invalid.';
    const BAD_REQUEST_ORDER_BANK_DOES_NOT_MATCH_PAYMENT_BANK                    = 'Bank code provided does not match order bank.';
    const BAD_REQUEST_PAYMENT_METHOD_DOES_NOT_MATCH_ORDER_METHOD                = 'Payment method provided does not match order method.';
    const BAD_REQUEST_ORDER_RECEIPT_REQUIRED                                    = 'The receipt field is required.';
    const BAD_REQUEST_ORDER_RECEIPT_NOT_UNIQUE                                  = 'Receipt should be unique.';

    // Sub Virtual Account
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_EXISTS                        = 'RazorpayX Lite account already exists for the sub merchant.';
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST                        = 'RazorpayX Lite account does not exist for the sub merchant with provided details.';
    const BAD_REQUEST_SUB_MERCHANT_NOT_LIVE_ACTION_DENIED                       = 'Fund movement has been put on hold because either the sub-account is not live yet or has been temporarily blocked. Please reach out to our support team for further assistance.';
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_ENABLED                       = 'RazorpayX Lite account is already enabled for the sub merchant.';
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_ALREADY_DISABLED                      = 'RazorpayX Lite account is already disabled for the sub merchant.';
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_DISABLED                     = 'Transfer is not allowed since RazorpayX Lite account is not active for the sub merchant. Please contact support for any further assistance.';
    const BAD_REQUEST_MASTER_MERCHANT_NOT_LIVE_ACTION_DENIED                    = 'Fund movement has been put on hold because either the master account is not live yet or has been temporarily blocked. Please reach out to our support team for further assistance.';
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_FEATURE_NOT_ENABLED                   = 'RazorpayX Lite account feature is not enabled for the sub merchant. Please reach out to our support team for further assistance.';
    const BAD_REQUEST_BUSINESS_BANKING_NOT_ENABLED_FOR_SUB_MERCHANT             = 'Business Banking is not enabled for sub-account. Please reach out to our support team for further assistance.';
    const BAD_REQUEST_BUSINESS_BANKING_NOT_ENABLED_FOR_MASTER_MERCHANT          = 'Business Banking is not enabled for master account. Please reach out to our support team for further assistance.';
    const BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_NOT_ENOUGH_BANKING_BALANCE   = 'Fund movement can\'t be performed due to insufficient balance. Please add more funds before proceeding.';

    const BAD_REQUEST_CUSTOMER_ALREADY_EXISTS                                   = 'Customer already exists for the merchant';
    const BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED                                 = 'Customer contact number is not set';
    const BAD_REQUEST_CUSTOMER_CARD_ALREADY_EXISTS                              = 'Card already exists for the customer';
    const BAD_REQUEST_CUSTOMER_BANK_ALREADY_EXISTS                              = 'Bank already exists for the customer';
    const BAD_REQUEST_CUSTOMER_WALLET_ALREADY_EXISTS                            = 'Wallet already exists for the customer';
    // Local customer cannot be duplicated because the parent is not a global customer.
    const BAD_REQUEST_CUSTOMER_DUPLICATE_NOT_GLOBAL                             = 'Customer cannot be created';
    const BAD_REQUEST_GLOBAL_CUSTOMER_MISMATCH                                  = 'Global customer does not match with the customer found';

    const BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED                              = 'OTP verification failed because attempt threshold has been reached. Please generate another otp.';
    const BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED                                 = 'SMS sending failed because threshold has been reached. Please try again later.';
    const SERVER_ERROR_EMAIL_LOGIN_OTP_REDIS_ERROR                              = 'An error occurred with redis during email login otp flow.';
    const SERVER_ERROR_EMAIL_VERIFICATION_OTP_REDIS_ERROR                       = 'An error occurred with redis during email verification otp flow.';
    const BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED                  = 'Email OTP could not be sent because threshold has been reached. Please try again later.';
    const BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED           = 'Email Verification OTP could not be sent because threshold has been reached. Please try again later.';

    const BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED                = 'Login OTP verification attempt limit reached. Your account has been locked. Please contact support.';
    const SERVER_ERROR_LOGIN_OTP_VERIFICATION_REDIS_ERROR                       = 'An error occurred with redis during login otp verification flow.';
    const SERVER_ERROR_VERIFY_OTP_VERIFICATION_REDIS_ERROR                      = 'An error occurred with redis during otp verification flow.';
    const BAD_REQUEST_OTP_LOGIN_LOCKED                                          = 'User account is locked due to too many incorrect OTP login attempts.';
    const BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED         = 'OTP verification attempt limit reached. Please try again later.';
    const BAD_REQUEST_INCORRECT_OTP                                             = 'Verification failed because of incorrect OTP.';
    const BAD_REQUEST_SMS_FAILED                                                = 'SMS sending failed.';

    const BAD_REQUEST_SMS_OTP_FAILED                                            = 'SMS delivery failed, please try after sometime';
    const BAD_REQUEST_EMAIL_OTP_FAILED                                          = 'Email delivery failed, please try after sometime';
    const BAD_REQUEST_LOGO_NOT_PRESENT                                          = 'The input does not contain a file named logo';
    const BAD_REQUEST_MERCHANT_LOGO_TOO_BIG                                     = 'Size of the logo is too big. Upload a smaller file size.';
    const BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE                                  = 'The height and width of the logo are not the same. Upload a square image.';
    const BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE                                   = 'The image type is not jpg, jpeg or png.';
    const BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL                                   = 'The dimensions of the image are too small. Minimum dimensions should be 256x256';
    const BAD_REQUEST_ACCOUNT_RECOVERY_PAN_DID_NOT_MATCH                        = 'PAN did not match with owner accounts found';

    const BAD_REQUEST_SHARED_TERMINAL_CANNOT_BE_COPIED                          = 'Shared terminal cannot be copied';
    const BAD_REQUEST_SHARED_TERMINAL_MERCHANT_CANNOT_BE_CHANGED                = 'Shared terminal merchant cannot be changed';
    const BAD_REQUEST_TERMINAL_NO_GATEWAY_MAPPING_FOR_DIRECTSETTLEMENT          = 'No terminal gateway mapping for direct settlement';
    const BAD_REQUEST_PLAN_ID_IS_NOT_REQUIRED                                   = 'plan_id cannot be passed with terminals';
    const BAD_REQUEST_BUY_PRICING_PLAN_WITH_NAME_DOES_NOT_EXIST                 = 'No pricing plan found with plan name';
    const BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL                 = 'Sub-Merchant already assigned to terminal';
    const BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL                   = 'Cannot change email of Sub-Merchant with same email as its parent';
    const BAD_REQUEST_UNSUPPORTED_BUSINESS_SUBCATEGORY                          = 'This operation is not supported for the given business subcategory';
    const BAD_REQUEST_USER_EMAIL_ALREADY_VERIFIED                               = 'User Email is already verified';

    const BAD_REQUEST_RECONCILIATION                                            = 'Error occurred during reconciliation';
    const BAD_REQUEST_INVALID_COUNTRY                                           = 'Invalid country code/name passed';

    const BAD_REQUEST_GATEWAY_CANNOT_TOPUP                                      = 'Payment processing failed because topup cannot be done';
    const BAD_REQUEST_INVALID_XML_SCHEMA                                        = 'Payment failed because of invalid data';

    const BAD_REQUEST_INVALID_CHECKOUT_ID                                       = 'The payment request has invalid checkout_id';
    const BAD_REQUEST_INVALID_PLATFORM                                          = 'The payment request has invalid platform';
    const BAD_REQUEST_INVALID_LIBRARY                                           = 'The payment request has invalid library';
    const BAD_REQUEST_INVALID_BROWSER                                           = 'The payment request has invalid browser';
    const BAD_REQUEST_INVALID_OS                                                = 'The payment request has invalid operating system';
    const BAD_REQUEST_INVALID_DEVICE                                            = 'The payment request has invalid device';
    const BAD_REQUEST_INVALID_INTEGRATION                                       = 'The payment required has invalid integration';
    const BAD_REQUEST_PAYMENT_ALREADY_REFUND_INITIATED                          = 'Refund already initiated';
    const BAD_REQUEST_PAYMENT_PROBLEM_IN_UPDATING                               = 'Problem in updating payment';
    const BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT                          = 'The fees calculated for payment is greater than the payment amount. Please provide a higher amount';
    const BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE              = 'The fees calculated for fund account validation is greater than available fee credits or balance.';
    const BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_SUPPORTED_BALANCE             = 'Penny Testing is not supported for the given account number.';
    const BAD_REQUEST_FUND_ACCOUNT_VALIDATION_BANK_NOT_ALLOWED                  = 'Sorry we do not support this bank right now for fund account validation.';
    const BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED                               = 'Payment created long back and cannot be cancelled now';
    const BAD_REQUEST_PAYMENT_ALREADY_UNDER_DISPUTE                             = 'Payment already has an open dispute';
    const BAD_REQUEST_DISPUTE_AMOUNT_GREATER_THAN_PAYMENT_AMOUNT                = 'Disputed amount cannot be greater than payment amount';
    const BAD_REQUEST_CANNOT_UPDATE_CLOSED_DISPUTE                              = 'This dispute is already closed and cannot be updated';

    const BAD_REQUEST_REPORTING_INTEGRATION                                     = 'Issue with Reporting Integration';
    const BAD_REQUEST_REPORTING_OTHER_ORG_INVALID_REQUEST                       = 'Invalid request for reporting from other org';
    const BAD_REQUEST_REPORTING_ADMIN_NOT_ALLOWED_MERCHANT_REPORTS              = 'Invalid request for reporting, admins are not allowed to access merchant level reports';

    const BAD_REQUEST_UFH_INTEGRATION                                           = 'Issue with UFH Integration';

    // batch processor related error codes
    const BAD_REQUEST_BATCH_FILE_INVALID_TYPE                                   = 'Incorrect type is used for the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_PAYMENT_ID                             = 'Payment Id is not set in the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_TRANSFER_ID                            = 'Transfer Id is not set in the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT                                 = 'Amount is not set in the uploaded file';
    const BAD_REQUEST_BATCH_FILE_INVALID_HEADERS                                = 'The uploaded file has invalid headers';
    const BAD_REQUEST_BATCH_FILE_EMPTY                                          = 'The uploaded file does not have any entries';
    const BAD_REQUEST_BATCH_FILE_DUPLICATE_PAYMENT_ID                           = 'The file should not have multiple entries for the same Payment Id';
    const BAD_REQUEST_BATCH_FILE_DUPLICATE_TRANSFER_ID                          = 'The file should not have multiple entries for the same Transfer Id';
    const BAD_REQUEST_BATCH_FILE_DUPLICATE_CONTACTS                             = 'The file should not have multiple entries for the same Email and Contact Number';
    const BAD_REQUEST_BATCH_FILE_ALREADY_PROCESSED                              = 'The uploaded file is already processed';
    const BAD_REQUEST_BATCH_FILE_UNDER_PROCESSING                               = 'The uploaded file is being processed';
    const BAD_REQUEST_BATCH_FILE_INVALID_SPEED                                  = 'Invalid speed. Should be \'optimum\' or \'normal\'';
    const BAD_REQUEST_BATCH_FILE_INSTANT_REFUNDS_DISABLED                       = 'Instant Refund feature is not enabled for your account';
    const BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS                       = 'Request failed because another operation on the batch is in progress';
    const BAD_REQUEST_LAMBDA_ANOTHER_OPERATION_IN_PROGRESS                      = 'Request failed because another operation of lambda is in progress';
    const BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT                                   = 'The uploaded file exceeds the number of entries allowed';
    const BAD_REQUEST_BATCH_STATS_NOT_SUPPORTED_FOR_TYPE                        = 'Batch stats are not available for this batch type';
    const BAD_REQUEST_BATCH_FILE_INVALID_RZP_REF_NO                             = 'The file should not have empty or non-numeric RZP Reference numbers';
    const BAD_REQUEST_BATCH_FILE_INVALID_COMMENT                                = 'The file should not have empty comments';
    const BAD_REQUEST_PAYOUT_BATCH_FILE_MISSING_MANDATORY_HEADERS               = 'The file you are trying to upload is missing one of the mandatory/conditionally mandatory header';

    const BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS                  = 'Request failed because another settlement operation in progress';
    const BAD_REQUEST_SETTLEMENT_RECONCILIATION_IN_PROGRESS                     = 'Request failed because another settlement reconciliation operation in progress';
    const BAD_REQUEST_SETTLEMENT_VERIFICATION_IN_PROGRESS                       = 'Request failed because another settlement verification operation in progress';

    const BAD_REQUEST_INVALID_MERCHANT_INVOICE_NUMBER                           = 'Invalid Invoice Number.';

    const BAD_REQUEST_FUND_TRANSFER_ANOTHER_OPERATION_IN_PROGRESS               = 'Request failed because another fund transfer operation in progress';
    const BAD_REQUEST_FUND_TRANSFER_FTS_ANOTHER_OPERATION_IN_PROGRESS           = 'Request failed because another fund transfer operation via fts in progress';
    const BAD_REQUEST_FEE_RECOVERY_ANOTHER_OPERATION_IN_PROGRESS                = 'Request failed because another operation is in progress';

    const BAD_REQUEST_PERMISSION_ERROR                                          = 'Permissions not found for this request/route';
    const BAD_REQUEST_INVALID_MAILGUN_WEBHOOK_TYPE                              = 'Invalid type specified for callback';
    const BAD_REQUEST_INVALID_MAILGUN_SIGNATURE                                 = 'Mailgun signature validation failed';
    const BAD_REQUEST_BANK_REQUIRED_WITH_ACCOUNT_NUMBER                         = 'Bank code should be provided in input if account number is sent';

    const BAD_REQUEST_PASSWORD_EXPIRED                                          = 'Account password has expired. Please contact administrator';
    const BAD_REQUEST_INVOICE_EXPIRE_FAILED                                     = 'Invoice expiry failed as payment exists or is in progress for this invoice.';
    const BAD_REQUEST_INVOICE_FEE_BEARER_CUSTOMER                               = 'Invoices disabled because fee bearer is customer';
    const BAD_REQUEST_PAYMENT_LINK_BATCH_ISSUED_ALREADY                         = 'Some/all payment links of given batch has been issued already';
    const BAD_REQUEST_BATCH_NOTIFICATIONS_SENT_ALREADY                          = 'Notification for this batch has already been sent';
    const BAD_REQUEST_PAYMENT_LINK_SLUG_GENERATE_FAILED                         = 'This URL is already taken, please try a different value';

    const BAD_REQUEST_ITEM_INACTIVE                                             = 'Item cannot be used as it is inactive';
    const BAD_REQUEST_ITEM_EDIT_NOT_ALLOWED                                     = 'You can not edit/delete an item with which invoices have been created already';
    const BAD_REQUEST_INVALID_ITEM_TAX_DETAILS                                  = 'Tax details provided for line item is invalid';
    const BAD_REQUEST_LINK_TYPE_HAS_NO_TAXATION                                 = 'Payment link does not support taxation';

    // Features
    const BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE                               = 'You cannot change the value of this feature';
    const BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE                       = 'You cannot enable/disable features in live mode';
    const BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED                         = 'The given feature is already assigned to the merchant';
    const BAD_REQUEST_MERCHANT_NOT_EXIST                                        = 'The given merchant ID is not present';
    const BAD_REQUEST_MERCHANT_FEATURE_NOT_EXIST                                = 'The given feature do not exist for above merchants. Rest are processed';
    const BAD_REQUEST_MERCHANT_FEATURE_ONBOARDING_STATUS_INVALID                = 'The product onboarding status provided is invalid';
    const BAD_REQUEST_MERCHANT_FEATURE_ACTIVATION_FORM_ALREADY_SUBMITTED        = 'The product activation form is already submitted.';

    const BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE                              = 'The requested feature is unavailable.';
    const BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER            = 'Sub-merchant no-doc onboarding is not enabled for partner';
    const BAD_REQUEST_TNC_ACCEPTANCE_AND_IP_NOT_TOGETHER                        = 'Both tnc_accepted and ip fields are required while accepting tnc';

    const BAD_REQUEST_INVALID_ADMIN_EMAIL                                       = 'Email provided is not a registered email';
    const BAD_REQUEST_INVALID_ADMIN_EMAIL_HOSTNAME                              = 'Email provided does not have the correct hostname';
    const BAD_REQUEST_AUTHENTICATION_FAILED                                     = 'Authentication failed';

    const BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED             = 'The sum of amount requested for transfer is greater than the captured amount';
    const BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_UNTRANSFERRED        = 'Transfer amount exceeds amount available for transfer.';
    const BAD_REQUEST_PAYMENT_TRANSFER_ENTITIES_NOT_SET                         = 'Payment transfer entities provided are invalid or not set';
    const BAD_REQUEST_PAYMENT_TRANSFER_MORE_THAN_ONE_CUSTOMER                   = 'Payment cannot be transferred to more than one customer';
    const BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN              = 'Payment cannot be transferred to multiple types of entities';
    const BAD_REQUEST_PAYMENT_TRANSFER_CURRENCY_MISMATCH                        = 'Transfer request currency must be same as payment currency';

    const BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE                             = 'Account does not have sufficient balance to carry out transfer operation';
    const BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID                               = 'Account ID provided for transfer is invalid';
    const BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED                            = 'The account needs to be activated by Razorpay before you can make transfers in live mode';
    const BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_TRANSFERRED         = 'The reversal amount provided is greater than amount transferred';
    const BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_UNREVERSED          = 'The reversal amount provided is greater than the amount not reversed';
    const BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE                    = 'The linked account does not have sufficient balance to process a reversal.';
    const BAD_REQUEST_LA_TRANSFER_REVERSAL_PERMISSION_MISSING                   = 'The linked account does not have permission to reverse a transfer';
    const BAD_REQUEST_TRANSFER_FOR_LA_REVERSAL_INVALID                          = 'The transfer can not be reversed by the linked account';
    const BAD_REQUEST_ORDER_TRANSFER_ENTITIES_NOT_SET                           = 'Order transfer entities provided are invalid or not set';
    const BAD_REQUEST_TRANSFER_NOT_ALLOWED_TO_SUSPENDED_LINKED_ACCOUNT          = 'Transfer cannot be made to a suspended linked account';

    const BAD_REQUEST_UPDATE_ON_HOLD_ALREADY_SETTLED                            = 'The hold attributes cannot be modified as the amount has already been settled to your account.';

    const BAD_REQUEST_USER_ACCOUNT_LOCKED                                       = 'Your account has been locked';
    const BAD_REQUEST_USER_ACCOUNT_DISABLED                                     = 'Your account has been disabled';
    const BAD_REQUEST_USER_NOT_AUTHENTICATED                                    = 'The user is not authenticated';
    const BAD_REQUEST_USER_NOT_FOUND                                            = 'User not found with the given input';
    const BAD_REQUEST_USER_ID_NOT_EXPECTED_IN_INPUT                             = 'User ID not expected in the given input';
    const BAD_REQUEST_USER_ROLE_INVALID                                         = 'The given role is not supported';
    const BAD_REQUEST_USER_OAUTH_PROVIDER_INVALID                               = 'The given oauth provider is not supported';
    const BAD_REQUEST_TOKEN_EXPIRED_NOT_VALID                                   = 'Token Expired or Not a valid token.';
    const BAD_REQUEST_OLD_PASSWORD_MISMATCH                                     = 'Old Password mismatch';
    const BAD_REQUEST_CAPTCHA_FAILED                                            = 'Captcha Failed';
    const BAD_REQUEST_CAPTCHA_SCORE_LOW                                         = 'Low captcha score';
    const BAD_REQUEST_INCORRECT_LOGIN_ATTEMPT                                   = 'Incorrect Password login attempt exhausted. Please contact support or login via dashboard';
    const BAD_REQUEST_TOKEN_ABSENT_FOR_RECURRING_PAYMENT                        = 'Token absent for recurring payment';
    const BAD_REQUEST_TOKEN_NOT_FOUND                                           = 'Token not found.';
    const BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED                              = 'That mobile number is associated with multiple accounts. Please use your email address to login.';
    const BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED                                    = 'No accounts associated with this contact mobile.';
    const BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED                               = 'The contact mobile is not verified.';
    const BAD_REQUEST_EMAIL_NOT_VERIFIED                                        = 'The email address is not verified.';
    const BAD_REQUEST_CONTACT_MOBILE_ALREADY_VERIFIED                           = 'Mobile number already verified.';
    const BAD_REQUEST_EMAIL_ALREADY_VERIFIED                                    = 'Email already verified.';
    const BAD_REQUEST_PASSWORD_INCORRECT                                        = 'The password you have entered is incorrect.';

    const BAD_REQUEST_USER_2FA_ALREADY_SETUP                                    = 'User already has a verified mobile number associated with the account';
    const BAD_REQUEST_LOCKED_USER_LOGIN                                         = 'User cannot login. User account is locked.';
    const BAD_REQUEST_USER_2FA_LOCKED                                           = 'User can\'t trigger 2FA OTP since 2FA is locked on this account';
    const BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP                                   = 'Verification failed because of incorrect OTP.';
    const BAD_REQUEST_ADMIN_2FA_LOGIN_INCORRECT_OTP                             = 'Verification failed because of incorrect OTP.';
    const BAD_REQUEST_2FA_SETUP_INCORRECT_OTP                                   = 'Second factor authentication setup failed because of incorrect OTP';
    const BAD_REQUEST_RESTRICTED_USER_CANNOT_SETUP_2FA                          = 'User is restricted by its associated merchant to perform the action';
    const BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED                               = 'Second factor authentication is enabled for user. OTP field is required';
    const BAD_REQUEST_USER_2FA_SETUP_REQUIRED                                   = 'User 2FA setup is required';
    const BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED                             = 'User 2FA setup is required before user logins.';
    const BAD_REQUEST_2FA_SETUP_USER_2FA_NOT_ENABLED                            = 'Second factor authentication is not enabled for the user.';
    const BAD_REQUEST_2FA_SETUP_ACCOUNT_LOCKED                                  = 'User account is locked.';
    const BAD_REQUEST_USER_2FA_ENFORCED                                         = 'Second factor authentication is mandated by one (or more) merchants';
    const BAD_REQUEST_OPERATION_ONLY_ALLOWED_BY_OWNER                           = 'Operation is only allowed by the owner of the merchant.';
    const BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY                                 = 'Owner 2FA setup should already be done to perform this action';
    const BAD_REQUEST_MERCHANT_RESTRICTED_SETTINGS_NOT_APPLIED                  = 'Merchant Restricted Settings failed to apply because users of merchant are associated with multiple merchants';
    const BAD_REQUEST_USER_OTP_REQUIRED                                         = 'OTP is required';
    const BAD_REQUEST_INVALID_ID_TOKEN                                          = 'Invalid ID token';
    const BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION                     = 'Restricted user cannot perform action';
    const BAD_REQUEST_USER_2FA_VALIDATION_REQUIRED                              = 'User\'s 2FA validation is required for this action';
    const BAD_REQUEST_ORG_2FA_ENFORCED                                          = 'Second factor authentication is mandated by one (or more) organization(s)';
    const BAD_REQUEST_2FA_DISABLED_FOR_DEMO_ACC                                 = 'Second factor authentication disabled for Banking Demo Account';

    const BAD_REQUEST_USER_WITH_ROLE_ALREADY_EXISTS                             = 'User with given role already exists';
    const BAD_REQUEST_ACTION_NOT_ALLOWED_FOR_SELF_USER                          = 'Action not allowed for self user';
    const BAD_REQUEST_USER_DOES_NOT_BELONG_TO_MERCHANT                          = 'User doesn\'t belong to the current merchant';
    const BAD_REQUEST_INVITATION_USER_ALREADY_INVITED                           = 'Invitation is already sent to this email';
    const BAD_REQUEST_INVITATION_USER_ALREADY_MEMBER                            = 'User with given email is already a member of the team';
    const BAD_REQUEST_INVITATION_CREATE_FAILED                                  = 'Invitation create failed either due to user invited is restricted or merchant is restricted';
    const BAD_REQUEST_INVITATION_ACCEPT_FAILED                                  = 'Invitation accept failed either due to user invited is restricted or merchant is restricted';
    const BAD_REQUEST_ADMIN_SELF_EDIT_PROHIBITED                                = 'SuperAdmin/Admin cannot edit their own preferences';
    const BAD_REQUEST_ADMIN_SELF_INVITE_PROHIBITED                              = 'Self-Invitation not allowed';
    const BAD_REQUEST_SUPERADMIN_ROLE_NOT_EDITABLE                              = 'SuperAdmin Role is not editable';

    const BAD_REQUEST_MERCHANT_HANDLE_UPPERCASE_ONLY                            = 'Merchant handle must be in uppercase.';
    const BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED                            = 'Merchant activation form has been locked for editing by admin.';
    const BAD_REQUEST_STAKEHOLDER_ALREADY_EXISTS                                = 'Stakeholder already exists for the merchant';
    const BAD_REQUEST_MERCHANT_BUSINESS_NAME_REQUIRED                           = 'Business Name is required';
    const BAD_REQUEST_MERCHANT_PAN_NAME_REQUIRED                                = 'PAN Name is required';
    const BAD_REQUEST_MERCHANT_EXTRA_FIELDS_PRESENT_IN_INPUT                    = 'Extra details present in input';
    const BAD_REQUEST_UNREGISTERED_NOT_SUPPORTED                                = 'We are not supporting (unregistered businesses) at the moment. We shall inform you when we start supporting';
    const BAD_REQUEST_UNSUPPORTED_BUSINESS_CATEGORY                             = 'We don\'t support this business category';
    const BAD_REQUEST_MERCHANT_DETAIL_FILE_TYPE                                 = 'Invalid File format. Only pdf, png and jpg is allowed.';
    const BAD_REQUEST_CASHBACK_CRITERIA_MISSING                                 = 'Either of percent_rate, min_txn_amount, max_cashback, min_cashback is required';
    const BAD_REQUEST_INVALID_OFFER_DURATION                                    = 'Offer end date must be later than offer start date';
    const BAD_REQUEST_OFFER_ALREADY_EXISTS                                      = 'Offer already exists. Please check the values and try again';
    const BAD_REQUEST_FLAT_CASHBACK_WITH_PERCENT_RATE_OR_MAX_CASHBACK           = 'Flat cashback cannot be combined wih percent rate or max cashback in an offer';
    const BAD_REQUEST_MAX_CASHBACK_WITHOUT_PERCENT_RATE                         = 'Max cashback should be combined wih percent rate';
    const BAD_REQUEST_IINS_EDITABLE_FOR_CARD_OFFER                              = 'Iins can only be editable for card offer';
    const BAD_REQUEST_OFFER_ALREADY_DEACTIVATED                                 = 'Offer has already been deactivated';
    const BAD_REQUEST_INVALID_PERMISSIONS_USAGE                                 = 'Combination of permissions used or assigned are invalid. Contact Razorpay Support';

    const BAD_REQUEST_API_CAPTURE_FAILED                                        = 'Error while recording capture on API side';

    const BAD_REQUEST_INVALID_PAYMENT_METHOD                                    = 'Payment method invalid / not allowed';
    const BAD_REQUEST_INVALID_GATEWAY_FOR_METHOD                                = 'Gateway not valid for payment method';

    const BAD_REQUEST_ES_DEBUG_METHOD_NOT_VALID                                 = 'Es debug method is not valid';
    const SERVER_ERROR_NO_ES_PRICING_FOR_POSTPAID_MERCHANT                      = 'ES scheduled default pricing cannot be assigned to postpaid Merchant';
    const SERVER_ERROR_INVALID_ES_PRICING                                       = 'Invalid ES pricing was assigned to this merchant';
    const SERVER_ERROR_QR_CODE_GENERATION_FAILURE                               = 'QR Code creation failed because of internal server error';

    const BAD_REQUEST_QR_CODE_CONFIG_NO_PREVIOUS_CONFIG                         = 'There is no previous config to be updated for the merchant.';
    const BAD_REQUEST_QR_CODE_CONFIG_EXPERIMENT_NOT_ENABLED_FOR_MERCHANT        = 'The QrCode Config Experiment is not enabled for merchant';
    const BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_NON_POSITIVE_CUTOFF   = 'The cut off time should be greater than zero';
    const BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_TOO_HIGH              = 'The cut off time cannot be greater than 86400';
    const BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_EMPTY                 = 'The cut off time is required';
    const BAD_REQUEST_QR_CODE_CONFIG_INVALID_CUT_OFF_TIME_ALPHA_NUMERIC         = 'The cutoff time should be an integer';

    const BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING                                 = 'Incomplete data for force authorization';

    const BAD_REQUEST_FILE_NOT_FOUND                                            = 'There was error while retrieving the file';

    const BAD_REQUEST_MERCHANT_USER_ACTION_NOT_SUPPORTED                        = 'The input action is not supported for the merchant user';
    const BAD_REQUEST_ACCESS_DENIED                                             = 'Access Denied';
    const BAD_REQUEST_DASHBOARD_IP_NOT_WHITELISTED                              = 'Dashboard cant be accessed from the current location';
    const BAD_REQUEST_IP_NOT_WHITELISTED                                        = 'This transaction is prohibited. Contact Support for help.';
    const BAD_REQUEST_IP_WHITELISTING_NOT_ALLOWED_WHEN_OPTED_OUT                = 'IP whitelisting is not allowed when you have opted out.';
    const BAD_REQUEST_IP_FORMAT_INVALID                                         = 'One or more ips are not valid as per IPv4 nd IPv6.';

    // Workflow Related Errors
    const BAD_REQUEST_MORE_THAN_ONE_ROLE_FOR_A_LEVEL                            = 'Migration not supported for workflows having steps with more than one role at any level.';
    const BAD_REQUEST_WORKFLOW_ENTITY_NOT_FOUND                                 = 'Workflow entity not found';
    const BAD_REQUEST_WORKFLOW_ENTITY_ID_NOT_FOUND                              = 'Workflow entity ID not found';
    const BAD_REQUEST_CHECK_NOT_REQUIRED_IN_CURRENT_LEVEL                       = 'No check required from checker roles in the current workflow action level';
    const BAD_REQUEST_ACTION_INVALID_TYPE                                       = 'The given action type is not valid';
    const BAD_REQUEST_ACTION_INVALID_METHOD                                     = 'The given action method is not valid';
    const BAD_REQUEST_WORKFLOW_INVALID_CHECKER                                  = 'The checker review is invalid for this action';
    const BAD_REQUEST_WORKFLOW_ACTION_NOT_FOUND                                 = 'The requested action is not found';
    const BAD_REQUEST_WORKFLOW_ACTION_CLOSE_NOT_AUTHORIZED                      = 'Workflow Action can only be closed by maker.';
    const BAD_REQUEST_WORKFLOW_ACTION_CLOSED                                    = 'The workflow action is closed.';
    const BAD_REQUEST_ACTION_NOT_APPROVED                                       = 'The requested action is not in approved state';
    const BAD_REQUEST_ACTION_ALREADY_EXECUTED                                   = 'The requested action has already been executed';
    const BAD_REQUEST_WORKFLOW_UPDATE_OR_DELETE_NOT_ALLOWED                     = 'Updating or Deleting a workflow is not allowed when there are open actions';
    const BAD_REQUEST_WORKFLOW_RULES_UPDATE_OR_DELETE_NOT_ALLOWED               = 'Workflow payout amount rules have already been created';
    const BAD_REQUEST_WORKFLOW_NOT_ACCESSIBLE                                   = 'Workflow does not belong to one merchant';
    const BAD_REQUEST_WORKFLOW_STEP_LEVEL_SEQUENCE                              = 'The levels in the steps should be increment of one';
    const BAD_REQUEST_WORKFLOW_STEP_ROLE_LEVEL_UNIQUE                           = 'The role and level combination should be unique';
    const BAD_REQUEST_WORKFLOW_PERMISSIONS_CANNOT_BE_REMOVED                    = 'Permissions associated with a workflow cannot be removed';
    const BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS                                = 'One of the permissions already has a workflow defined';
    const BAD_REQUEST_PERMISSION_DISABLED_FOR_WORKFLOW                          = 'Some of the permissions passed cannot be used to create a workflow.';
    const BAD_REQUEST_WORKFLOW_ANOTHER_ACTION_IN_PROGRESS                       = 'Other actions on the entity are in progress.';
    const BAD_REQUEST_WORKFLOW_STEP_OP_MISMATCH                                 = 'The op type does not match with other steps in the same level';
    const BAD_REQUEST_WORKFLOW_ACTION_CLOSE_UNAUTHORIZED                        = 'An action can only be closed by maker';
    const BAD_REQUEST_WORKFLOW_CLOSE_NOT_SUPPORTED                              = 'Close is not supported for the given workflow';
    const BAD_REQUEST_ACTION_NOT_IN_OPEN_STATES                                 = 'Workflow action is not in any open state';
    const BAD_REQUEST_WORKFLOW_DUTY_TYPE_INVALID                                = 'Workflow requests listing duty/type params are invalid';
    const BAD_REQUEST_WORKFLOW_ENTITY_VALIDATOR_RULE_NOT_FOUND                  = 'Workflow entity validator not found';
    const BAD_REQUEST_INVALID_STATE                                             = 'Invalid state passed in query parameters';
    const BAD_REQUEST_INVALID_WORKFLOW_FOR_PAYOUT                               = 'Workflow does not have create_payout permission';
    const BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND                             = 'Required permission not found';
    const BAD_REQUEST_BATCH_TYPE_PERMISSION_MISSING                             = 'Batch type permission missing';
    const BAD_REQUEST_PAYOUT_WORKFLOW_EDIT_IN_PROGRESS                          = 'Workflow edit on the same payout rule is active';
    const BAD_REQUEST_PAYOUT_INVALID_STATE                                      = 'Payout is not in pending state';
    const BAD_REQUEST_USER_ROLE_NOT_SUPPORTED_FOR_WORKFLOW                      = 'User role is not supported for workflows';
    const BAD_REQUEST_OPEN_WORKFLOW_NOT_FOUND                                   = 'No open workflow found to submit clarification';

    const BAD_REQUEST_SUPERADMIN_ACCESS_REQUIRED                                = 'This service can only be accessed by superadmins';

    const BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD                               = 'Load across all gateway rules must be less than 100 percent';

    const BAD_REQUEST_INVALID_OAUTH_MAIL_TYPE                                   = 'Invalid type sent for oauth mail.';
    const BAD_REQUEST_AUTH_SERVICE_ERROR                                        = 'There was an error completing this request';

    const BAD_REQUEST_INVALID_WORKFLOW_FOR_NEED_MERCHANT_CLARIFICATION          = 'Workflow not supported for need merchant clarification';
    const BAD_REQUEST_MERCHANT_NOT_FOUND_FOR_NEED_MERCHANT_CLARIFICATION        = 'Did not found merchant to notify for need clarification';

    const BAD_REQUEST_COUPON_LIMIT_REACHED                                      = 'Coupon code limit reached';
    const BAD_REQUEST_COUPON_ALREADY_USED                                       = 'Coupon code already used';
    const BAD_REQUEST_INVALID_COUPON_CODE                                       = 'Coupon code not found';
    const BAD_REQUEST_COUPON_NOT_VALID_FOR_MERCHANT                             = 'Coupon code not valid for this merchant';
    const BAD_REQUEST_COUPON_NOT_APPLICABLE                                     = 'Coupon code is not applicable right now';
    const BAD_REQUEST_COUPON_EXPIRED                                            = 'Coupon code is expired';
    const BAD_REQUEST_COUPON_ALREADY_EXISTS                                     = 'Coupon code already exists';

    const BAD_REQUEST_COUPON_REQUEST_TIMED_OUT                                  = 'Coupon code apply request timeout';
    const BAD_REQUEST_ONLY_AMOUNT_CREDITS_COUPON_APPLICABLE                     = 'Only Amount Credits Coupon Code is allowed';

    const BAD_REQUEST_SNS_PUBLISH_FAILED                                        = 'Sns Publish failed';

    const BAD_REQUEST_ADMIN_TOKEN_MISMATCH                                      = 'Admin Token Mismatch';

    const BAD_REQUEST_PAYMENT_PENDING_AUTHORIZATION                             = 'Payment is pending authorization from approver.';
    const BAD_REQUEST_PAYMENT_PENDING                                           = 'Payment failed';

    const BAD_REQUEST_GATEWAY_FILE_NON_RETRIABLE                                = 'This gateway file generation attempt is not retriable';
    const BAD_REQUEST_GATEWAY_FILE_ANOTHER_OPERATION_IN_PROGRESS                = 'Another operation is in progress on the gateway_file entity';
    const BAD_REQUEST_MORPHED_ENTITY_INVALID                                    = 'Invalid entity passed in query parameters';

    const BAD_REQUEST_INSUFFICIENT_BALANCE_FOR_ADJUSTMENT                       = 'Merchant does not have enough balance for negative adjustment';
    const BAD_REQUEST_BALANCE_DOES_NOT_EXIST                                    = 'Balance does not exist';

    const BAD_REQUEST_PAYMENT_LINK_NOT_PAYABLE                                  = 'Payment cannot be made on this payment link';

    const SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND                               = 'No data present for gateway file processing in the given time period';
    const SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE                       = 'Error occurred trying to create file';
    const SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE                          = 'Error occurred while sending file';
    const SERVER_ERROR_GATEWAY_FILE_CLAIMS_LESSER_THAN_REFUNDS                  = 'Combined file not sent as claims is lesser than refunds';
    const SERVER_ERROR_GATEWAY_FILE_LOGICAL_ERROR_REFUNDS_OUT_OF_RANGE          = 'Refunds out of expected date range';

    const BAD_REQUEST_MERCHANT_REQUEST_INVALID_NAME                             = 'The merchant request name is invalid';
    const BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING                      = 'The merchant request submissions are missing';

    const BAD_REQUEST_LINKED_ACCOUNT_NOTES_KEY_MISSING                          = 'Keys sent in linked_account_notes must exist in notes';
    const BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT                             = 'The account provided is not a linked account';
    const BAD_REQUEST_DASHBOARD_ACCESS_REQUIRED_TO_ALLOW_REVERSALS              = 'Dashboard access must be enabled to allow customer refunds.';
    const BAD_REQUEST_LINKED_ACCOUNT_DASHBOARD_ACCESS_ALREADY_GIVEN             = 'Linked Account dashboard access is already given to the merchant';
    const BAD_REQUEST_NO_EMAIL_LINKED_ACCOUNT_DASHBOARD_ACCESS                  = 'No Valid email address present to provide linked account dashboard access';
    const BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS                         = 'No Linked Account dashboard users to revoke access';
    const BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_INSTANTLY_ACTIVATED              = 'Linked account cannot be instantly activated';
    const BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_GIVEN             = 'Linked Account reversal ability is already given to the merchant';
    const BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_REMOVED           = 'Linked Account reversal ability is already removed from the merchant';
    const BAD_REQUEST_LINKED_ACCOUNT_CREATION_WITH_DUPLICATE_EMAIL_NOT_ENABLED  = 'Feature to create Linked Account with existing emails is not allowed for this merchant. Please contact support.';

    // Partners
    const BAD_REQUEST_OAUTH_APP_NOT_FOUND                                       = 'Oauth app could not be found. Create an app to continue.';
    const BAD_REQUEST_CLIENT_APPLICATION_NOT_MAPPED                             = 'The client is not mapped to the application';
    const BAD_REQUEST_PARTNER_TYPE_INVALID                                      = 'Partner type is invalid';
    const BAD_REQUEST_PARTNER_TYPE_REQUIRED                                     = 'Partner type is required';
    const SERVER_ERROR_PARTNER_APP_NOT_FOUND                                    = 'Partner app could not be found';
    const BAD_REQUEST_INVALID_PARTNER_ACTION                                    = 'Invalid partner action';
    const BAD_REQUEST_INVALID_APPLICATION_ID                                    = 'Invalid application id';
    const BAD_REQUEST_MISSING_APPLICATION_ID                                    = 'Application id is required';
    const BAD_REQUEST_INVALID_APPLICATION_TYPE                                  = 'Invalid application type';
    const BAD_REQUEST_APPLICATION_TYPE_UPDATE_NOT_SUPPORTED                     = 'Application type cannot be updated';
    const BAD_REQUEST_PARTNER_CONTEXT_NOT_SET                                   = 'Partner context must be set';
    const BAD_REQUEST_MERCHANT_IS_NOT_PARTNER                                   = 'Merchant is not a partner';
    const BAD_REQUEST_MERCHANT_IS_ALREADY_PARTNER                               = 'Merchant is already a partner';
    const BAD_REQUEST_PARTNER_CANNOT_BE_SUBMERCHANT_TO_ITSELF                   = 'Partner cannot add himself as a submerchant';
    const BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND                        = 'Partner and merchant are not linked';
    const BAD_REQUEST_PARTNER_OWNER_NOT_PRESENT_FOR_USER                        = 'Submerchant Primary owner not present';
    const BAD_REQUEST_MARK_AS_PARTNER_ALREADY_IN_PROGRESS                       = 'Mark as partner is in progress for the merchant';
    const BAD_REQUEST_ACCESS_NOT_ALLOWED_FOR_RESELLER                           = 'Reseller partner is not allowed the requested access';
    const BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_PARTNER                          = 'Linked account cannot be a partner';
    const BAD_REQUEST_PARTNER_SUBMERCHANT_WHITELABEL_ONBOARDING_EXP_NOT_ENABLED = 'Sub-merchant whitelabel onboarding feature not enabled for partner';
    const BAD_REQUEST_PARTNER_LOGO_TOO_BIG                                      = 'Size of the logo is too big. Upload a smaller file size';
    const BAD_REQUEST_PARTNER_LOGO_NOT_SQUARE                                   = 'The height and width of the logo are not the same. Upload a square image';
    const BAD_REQUEST_PARTNER_LOGO_NOT_IMAGE                                    = 'The image type is not jpg, jpeg or png';
    const BAD_REQUEST_PARTNER_LOGO_TOO_SMALL                                    = 'The dimensions of the image are too small. Minimum dimensions should be 256x256';
    const BAD_REQUEST_MANUAL_SETTLEMENT_NOT_ALLOWED                             = 'Manual settlements not enabled on the partner account. Please reach out to Razorpay support to enable.';

    const BAD_REQUEST_PAYMENT_MDR_UPDATE_IN_PROGRESS                            = 'Payments MDR backfill job is currently in progress';
    const BAD_REQUEST_CANNOT_ADD_MERCHANT_USER                                  = 'Cannot add sub-merchant user with given details';
    const BAD_REQUEST_CARD_ISSUER_INVALID                                       = 'Your payment could not be completed due to incorrect card details. Try another payment method or contact your bank for details. ';
    const BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT                       = 'Card not supported for fund account creation';
    const BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED                         = 'The sub-merchant accessed has not been activated. Please use test credentials for testing.';
    const BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS                 = 'Another payout operation for merchant is in progress. Please try again later.';
    const BAD_REQUEST_PAYOUT_ANOTHER_OPERATION_IN_PROGRESS                      = 'Another payout operation is in progress. Please try again later.';
    const BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED                            = 'The payout is already being processed.';
    const BAD_REQUEST_INVALID_PASSWORD                                          = 'Invalid password';
    const BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD                         = 'Your new password cannot match any of your last five passwords.';
    const BAD_REQUEST_INVALID_LOCALE                                            = 'Issue on bank side';
    const BAD_REQUEST_RETRY_ATTEMPT_LIMIT_EXCEEDED                              = 'Retry attempts limit exceeded';
    const BAD_REQUEST_COULD_NOT_READ_CARD_MAGNETIC_STRIPE                       = 'Could not read card magnetic stripe';
    const BAD_REQUEST_INVALID_CARD_DETAILS                                      = 'Invalid card details';
    const BAD_REQUEST_WEBHOOK_DETAILS_LOCKED_FOR_MFN                            = 'Webhooks are controlled by partner merchant and hence webhook creation is blocked';
    const BAD_REQUEST_PAYOUT_NOT_QUEUED_STATUS                                  = 'The payout is not in queued status';
    const BAD_REQUEST_PAYOUT_NOT_CREATED_STATUS                                 = 'The payout is not in created status';
    const BAD_REQUEST_PAYOUT_NOT_QUEUED_OR_SCHEDULED_STATUS                     = 'The payout is not in queued or scheduled status';
    const BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS                                 = 'The payout is not in pending status';
    const BAD_REQUEST_PAYOUT_NOT_BATCH_SUBMITTED_STATUS                         = 'The payout is not in batch_submitted status';
    const BAD_REQUEST_PAYOUT_NOT_CREATE_REQUEST_SUBMITTED_STATUS                = 'The payout is not in create_request_submitted status';
    const BAD_REQUEST_PAYOUT_WORKFLOW_ACTION_FAILED                             = 'An error occurred performing this action';
    const BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE                                   = 'An error occurred while creating the payout. Payout workflow could not be initiated';
    const BAD_REQUEST_PAYOUT_MODE_REQUIRED                                      = 'Mode is required for payout';
    const BAD_REQUEST_WORKFLOW_STATE_CALLBACK_DUPLICATE                         = 'A duplicate request to create state has come from the workflow service';
    const BAD_REQUEST_WORKFLOW_STATE_CALLBACK                                   = 'An error occurred while processing state';
    const SERVER_ERROR_WORKFLOW_STATE_INVALID_ID                                = 'Invalid workflow state Id';
    const BAD_REQUEST_PAYOUT_INVALID_MODE                                       = 'Payout mode is invalid';
    const BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED                                 = 'Mode is unsupported';
    const BAD_REQUEST_BUNDLE_PRICING_SUBSCRIPTION_SUPPORTED_IN_ONLY_LIVE_MODE   = 'Bundle pricing subscription valid only in Live mode';
    const BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_INVITE_EXCEEDED                   = 'Daily limit for submerchant add exceeded';
    const BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_ONBOARDING_EXCEEDED               = 'Daily limit for submerchant addition exceeded';
    const BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_FOR_PRODUCT                      = 'Payout mode CARD is disabled for your account';
    const BAD_REQUEST_M2P_MERCHANT_BLACKLISTED_BY_NETWORK                       = 'Payout to the card network via mode CARD is blocked by banking partner';

    const BAD_REQUEST_AMAZONPAY_PAYOUT_NOT_ALLOWED_ON_DIRECT_ACCOUNT            = 'Payouts to amazonpay wallet are not allowed on current accounts';

    const BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS                       = 'Your details could not be saved as another request is in progress. Please try again in sometime.';
    const BAD_REQUEST_EDIT_OPERATION_IN_PROGRESS                                = 'Your details could not be saved as another request is in progress. Please try again in sometime.';

    //Partner Activation error descriptions
    const BAD_REQUEST_PARTNER_ACTIVATION_OPERATION_IN_PROGRESS                      = 'Partner activation is in progress';
    const BAD_REQUEST_PARTNER_ACTIVATION_ALREADY_LOCKED                             = 'Partner activation details cannot be saved as partner activation form is locked';
    const BAD_REQUEST_PARTNER_ACTION_NOT_SUPPORTED                                  = 'provided partner action is not supported';
    const BAD_REQUEST_PARTNER_COMMISSIONS_ALREADY_ON_HOLD                           = 'partner commissions already on hold';
    const BAD_REQUEST_PARTNER_COMMISSIONS_ALREADY_RELEASED                          = 'partner commissions already released';
    const BAD_REQUEST_PARTNER_IS_NOT_ACTIVATED                                      = 'partner is not activated';
    const BAD_REQUEST_PARTNER_ADD_MINIMUM_SUBM                                      = 'please add minimum of 3 subMerchants to view the invoices';
    const BAD_REQUEST_MERCHANT_FORM_UNDER_NEEDS_CLARIFICATION                       = 'Cannot save partner form details since merchant form is under needs clarification';

    const BAD_REQUEST_ADDED_SUBMERCHANT_BUT_CC_APPLICATION_NOT_CREATED              = 'Capital sub-merchant has been added but corporate card application could not be created. Please contact support.';

    // Free payout
    const BAD_REQUEST_FREE_PAYOUT_SUPPORTED_MODES_ARRAY_DUPLICATE_VALUE         = 'Value in free payout supported modes array is duplicate.';
    const BAD_REQUEST_FREE_PAYOUT_UPDATE_ANOTHER_OPERATION_IN_PROGRESS          = 'Request failed because another operation is in progress';

    // Fee Recovery
    const BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED              = 'Creating/Updating an internal Razorpay Contact is not permitted';
    const BAD_REQUEST_FEE_RECOVERY_PAYOUT_CANCEL_NOT_PERMITTED                  = 'Cancelling a Fee Recovery Payout is not permitted';
    const BAD_REQUEST_INTERNAL_FUND_ACCOUNT_UPDATE_NOT_PERMITTED                = 'Updating an internal Razorpay Fund Account is not permitted';
    const BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED              = 'Creating a fund account for an Internal Razopay contact is not permitted';
    const BAD_REQUEST_FEE_RECOVERY_MANUAL_AMOUNT_MISMATCH                       = 'Amount recovered does not match with the total fees calculated for given payouts';
    const BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID         = 'Fee Recovery cannot be provided for a certain payout';
    const BAD_REQUEST_PAYOUT_TO_INTERNAL_FUND_ACCOUNT_NOT_PERMITTED             = 'Creating a payout to an internal Razorpay Fund Account is not permitted';
    const BAD_REQUEST_FEE_RECOVERY_INVALID_STATUS                               = 'Invalid status for fee recovery';
    const BAD_REQUEST_FEE_RECOVERY_INVALID_STATUS_TRANSITION                    = 'Invalid status transition for fee recovery';
    const BAD_REQUEST_FEE_RECOVERY_INCORRECT_BALANCE                            = 'Fee Recovery can only be done for banking balance of type direct';
    const BAD_REQUEST_FEE_RECOVERY_ALREADY_INITIATED                            = 'Fee Recovery already initiated for one or more payouts/reversals';
    const BAD_REQUEST_FEE_RECOVERY_AMOUNT_INSUFFICIENT                          = 'Amount is insufficient to make a fee recovery payout';
    const BAD_REQUEST_FEE_RECOVERY_CREATE_ATTEMPT_INVALID_SOURCE_ENTITY         = 'Attempting to create a fee recovery for an unsupported source entity';
    const BAD_REQUEST_FEE_RECOVERY_INVALID_TYPE                                 = 'Invalid type for fee recovery';
    const BAD_REQUEST_FEE_RECOVERY_BULK_UPDATE_ERROR                            = 'Error while updating status of existing payout\'s/reversal\'s fee_recovery entity';
    const BAD_REQUEST_FEE_RECOVERY_FOR_GIVEN_ENTITY_ALREADY_EXISTS              = 'Entry for given source entity already exists';
    const BAD_REQUEST_FEE_RECOVERY_FAILED_PAYOUT_TO_REVERSAL                    = 'Credit entry corresponding to reversal\'s payout already exists';
    const BAD_REQUEST_FEE_RECOVERY_INCORRECT_TIMESTAMPS                         = 'Start timestamp cannot be greater than end timestamp';
    const BAD_REQUEST_FEE_RECOVERY_INCORRECT_SCHEDULE_TYPE                      = 'Schedule type supported for fee recovery';
    const BAD_REQUEST_FEE_RECOVERY_INCORRECT_BALANCE_TYPE                       = 'Only Direct-Banking type balance is allowed';
    const BAD_REQUEST_FEE_RECOVERY_MANUAL_FOR_RZP_FEES_PAYOUT_NOT_SUPPORTED     = 'Manual recovery for rzp_fees type payouts is not supported';
    const BAD_REQUEST_FEE_RECOVERY_AMOUNT_ZERO                                  = 'Fee Recovery payout amount cannot be zero';

    const BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN                              = 'Request failed as contact mobile already taken';

    const BAD_REQUEST_NO_OWNER_ACCOUNTS_ASSOCIATED                              = 'No owner accounts associated with this contact mobile';
    const BAD_REQUEST_MULTI_OWNER_ACCOUNTS_ASSOCIATED                           = 'Multiple owner accounts associated with this contact mobile';


    //OTC Error Codes
    const BAD_REQUEST_ALREADY_PROCESSED                                      = 'Re-push response in case transaction was validated initially against the same challan number';
    const BAD_REQUEST_DUPLICATE_TRANSACTION                                  = 'Duplicate payment identified against an already paid Challan';
    const BAD_REQUEST_CHALLAN_NOT_FOUND                                  = 'Challan not found in system';
    const BAD_REQUEST_CHALLAN_EXPIRED                                        = 'Challan was expired on receipt of API push from the bank';
    const BAD_REQUEST_CLIENT_CODE_NOT_FOUND                                  = 'Client Code not found in system';
    //const BAD_REQUEST_AMOUNT_MISMATCH                                        = 'Discrepancy between expected challan amount and paid Amount';
    const BAD_REQUEST_IDENTIFICATION_ID_NOT_FOUND                            = 'Identification id not found in system';
    const BAD_REQUEST_MERCHANT_OTC_NOT_ENABLED                               = 'Merchant is not enabled on OTC';
    const BAD_REQUEST_CHALLAN_DOES_NOT_HAVE_ORDER                            = 'Challan does not have order';
    const BAD_REQUEST_CLIENT_CODE_CHALLAN_MISMATCH                           = 'Challan does not belong to the merchant';


    // Scheduled Payouts
    const BAD_REQUEST_SCHEDULED_PAYOUT_AUTH_NOT_SUPPORTED                       = 'Scheduled Payouts can only be created via dashboard';
    const BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_AUTH_NOT_SUPPORTED                = 'Scheduled Payouts can only be cancelled via dashboard';
    const BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIMESTAMP                        = 'Payouts can only be scheduled upto 3 months in advance';
    const BAD_REQUEST_SCHEDULED_PAYOUT_INVALID_TIME_SLOT                        = 'We currently only support scheduling payouts at certain time slots';
    const BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_REJECT_APPROVE_INVALID_TIMESTAMP  = 'No action can be performed on scheduled payouts that are ready to be processed';
    const BAD_REQUEST_PAYOUT_NOT_SCHEDULED_STATUS                               = 'The payout is not in scheduled status';

    // payout links
    const BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED                         = 'Error occurred at Payout Link Microservice';
    const BAD_REQUEST_CANNOT_GENERATE_OTP_WITHOUT_PHONE_AND_EMAIL              = 'Provided contact neither has email nor phone number';
    const BAD_REQUEST_CUSTOMER_OTP_DELIVERY_FAILED                             = 'Error in SMS/Email delivery for OTP';
    const BAD_REQUEST_INVALID_STATE_FOR_OTP_GENERATION                         = 'Cannot generate OTP for Payout Link in this state';
    const BAD_REQUEST_PAYOUT_LINK_SEND_EMAIL_FAILED                            = 'Invalid email_type. Please provide valid value';
    const BAD_REQUEST_INVALID_STATE_FOR_OTP_VERIFICATION                       = 'Cannot verify OTP for Payout Link in this state';
    const BAD_REQUEST_ONLY_VPA_AND_BANK_ACCOUNT_SUPPORTED                      = 'Only VPA and Bank Accounts supported by Payout Links';
    const BAD_REQUEST_INVALID_OTP_AUTH_TOKEN                                   = 'Token passed is either invalid or expired';
    const BAD_REQUEST_CUSTOMER_OTP_GENERATION_FAILED                           = 'OTP generation failed';
    const BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS                               = 'Invalid Payout Link status passed';
    const BAD_REQUEST_REWRITING_EMAIL_NOT_PERMITTED                            = 'Cannot over-write existing email information in created Payout Link';
    const BAD_REQUEST_REWRITING_PHONE_NUMBER_NOT_PERMITTED                     = 'Cannot over-write existing phone number information in created Payout Link';
    const BAD_REQUEST_INVALID_PAYOUT_LINK_NOTIFICATION_TYPE                    = 'Invalid Payout Link Notification Type';
    const BAD_REQUEST_PAYOUT_LINK_INVALID_STATUS_TRANSITION                    = 'The following state transition is not allowed for this payout link';
    const BAD_REQUEST_PAYOUT_LINK_CANNOT_BE_CANCELLED_IN_THIS_STATE            = 'Payout Link cannot be cancelled in this state';
    const BAD_REQUEST_PAYOUT_LINK_INVALID_STATE_FOR_INITIATE_REQUEST           = 'Cannot initiate Payout Link in this state';
    const BAD_REQUEST_PAYOUT_LINK_ANOTHER_OPERATION_IN_PROGRESS                = 'Request failed because another operation is in progress';
    const BAD_REQUEST_EITHER_CONTACT_ID_OR_INFORMATION_TO_BE_SENT              = 'Either Contact ID or Contact Information is required, not both';
    const BAD_REQUEST_EMAIL_NOTIFICATION_WITH_EMPTY_EMAIL                      = 'send_email cannot be true when contact does not have an associated email';
    const BAD_REQUEST_SMS_NOTIFICATION_WITH_EMPTY_PHONE                        = 'send_sms cannot be true when contact does not have an associated phone number ';
    const BAD_REQUEST_INVALID_CONTACT_ID                                       = 'Contact Id provided is invalid';
    const BAD_REQUEST_CONTACT_ID_EMAIL_AND_PHONE_NUMBER_MISSING                = 'Cannot create payout link as the contact_id provided does not have either email or phone number';
    const BAD_REQUEST_PAYOUT_LINK_SERVICE_UNDER_MAINTAINENCE                   = 'Payout Link service is under maintainence';
    const BAD_REQUEST_INVALID_BATCH_TYPE_FOR_PAYOUT_LINK_CREATE_BATCH          = 'Invalid batch type for Payout Links Batch';
    const BAD_REQUEST_BATCH_ID_MISSING_FOR_PAYOUT_LINK_PROCESS_BATCH           = 'Batch ID missing for Payout Links Process Batch';

    const BAD_REQUEST_BANK_ACCOUNT_TYPE_MISMATCH                               = 'Request object does not has \'type\' field set to \'org\'';
    const BAD_REQUEST_BANK_ACCOUNT_TYPE_NOT_FOUND                              = 'Request object does not has \'type\' field';
    const BAD_REQUEST_BANK_ACCOUNT_ENTITY_ID_NOT_PRESENT                       = 'Request object does not has \'entity_id\' which is a required field';
    const BAD_REQUEST_ORG_BANK_ACCOUNT_ALREADY_EXISTS                          = 'Org bank account with the given org id already exists';
    const BAD_REQUEST_ORG_NO_BANK_ACCOUNT_FOUND                                = 'Org bank account not found';
    const BAD_REQUEST_CONTACT_ID_MISSING_FOR_INVITATION                        = 'Contact Id missing for vendor portal invitation';
    const BAD_REQUEST_CONTACT_WITHOUT_EMAIL                                    = 'Contact does not have email id';

    const BAD_REQUEST_TO_EMAIL_ID_MISSING_FOR_INTEGRATION_INVITATION           = 'to_email_id missing for integration invitation';

    const SERVER_ERROR_CA_MERCHANT_IN_BULK_PAYOUT_VA_FLOW                      = 'CA merchant should not come into Bulk Payout VA flow';
    const SERVER_ERROR_BALANCE_RECORDS_NOT_AVAILABLE_FOR_MERCHANT              = 'Balance records are not available for the merchant';
    const BAD_REQUEST_BULK_PAYOUTS_PAYOUT_HEADER_MISMATCH                      = 'Payout amount header needs to be of either rupees or paise type';
    const BAD_REQUEST_VA_TO_VA_PAYOUTS_BLOCKED                                 = 'Payouts to RazorpayX Lite account is not enabled for your account. Please contact support for any further assistance';
    const BAD_REQUEST_VA_TO_VA_PAYOUTS_NOT_ALLOWED                             = 'Payouts between different RazorpayX Lite accounts is not allowed. Please contact support for any further assistance';
    const BAD_REQUEST_VA_TO_VA_PAYOUTS_NO_ACTIVE_BENEFICIARY_VA_FOUND          = 'The account associated with provided fund account is either not active or does not exist. please check';
    const BAD_REQUEST_VA_TO_VA_PAYOUT_ON_SAME_ACCOUNT                          = 'Payout to same banking account is blocked';
    const BAD_REQUEST_VA_TO_VA_PAYOUT_ALREADY_PROCESSED                        = 'Payout got processed already';
    const BAD_REQUEST_VA_TO_VA_PAYOUT_ALREADY_REVERSED                         = 'Payout got reversed already';

    const BAD_REQUEST_CREDIT_TRANSFER_ALREADY_PROCESSED                        = 'Credit transfer entity is already processed';
    const BAD_REQUEST_CREDIT_TRANSFER_ALREADY_FAILED                           = 'Credit transfer entity is already failed';
    const BAD_REQUEST_CREDIT_TRANSFER_ALREADY_BEING_PROCESSED                  = 'credit_transfer is already being processed';
    const BAD_REQUEST_NO_ACTIVE_VIRTUAL_ACCOUNT_FOUND                          = 'The account associated with provided details is either not active or does not exist. please check';

    // Merchant Config Inheritance
    const BAD_REQUEST_INHERITANCE_PARENT_SHOULD_BE_PARTNER_PARENT_OF_SUBMERCHANT    =  'Inheritance parent should be aggregator or fully-managed partner of the submerchant';

    // Terminal Onboarding
    const BAD_REQUEST_TERMINAL_ONBOARDING_DISABLED                              = 'Terminal onboarding feature is disabled';
    const BAD_REQUEST_ONLY_DEACTIVATED_TERMINALS_CAN_BE_ENABLED                 = 'Only deactivated terminals can be enabled';
    const BAD_REQUEST_ONLY_PENDING_OR_ACTIVATED_TERMINALS_CAN_BE_DISABLED       = 'Only pending or activated terminals can be disabled';
    const BAD_REQUEST_INVALID_MPAN                                              = 'The MPAN used is not issued to your account.';
    const BAD_REQUEST_INVALID_MPAN_FOR_NETWORK                                  = 'The MPAN used does not belong to the network.';
    const BAD_REQUEST_MCC_IS_BARRED                                             = 'The merchant`s mcc is barred.';

    // Scrooge
    const GATEWAY_VERIFY_REFUND_ABSENT                                          = 'Refund is not present at gateway';
    const GATEWAY_VERIFY_OLDER_REFUNDS_DISABLED                                 = 'Verification of older refunds is disabled';
    const REFUND_MANUALLY_CONFIRMED_UNPROCESSED                                 = 'Refund is marked unprocessed manually';
    const GATEWAY_PAYMENT_REVERSAL_VERIFICATION_DISABLED                        = 'Verification of payment reversal is disabled';
    const BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT                               = 'Payout amount including fees should be greater than Re 1';
    const GATEWAY_CHARGEBACK_REFUND_FAILURE                                     = 'Can not refund chargeback transaction';
    const GATEWAY_ERROR_MULTIPLE_REFUNDS_FOUND                                  = 'Multiple refunds found at gateway';
    const GATEWAY_ERROR_UNEXPECTED_STATUS                                       = 'Unexpected status from gateway';
    const GATEWAY_ERROR_REFUND_FAILED_PAYMENT_NOT_IDENTIFIED                    = 'Refund failed';
    const GATEWAY_ERROR_REFUND_DEEMED                                           = 'Refund is in pending status';

    const SERVER_ERROR_BATCH_SERVICE_UPLOAD_FAILURE                             = 'Batch file upload failed due to batch server error';

    const BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET                                  = 'Merchant context must be set';

    const BAD_REQUEST_MERCHANT_ID_NOT_PASSED                                    = 'Merchant id should be passed for this permission';

    const BAD_REQUEST_PAYMENT_REDIRECT_INVALID_AUTH                             = 'Payment failed';
    const BAD_REQUEST_PAYMENT_REDIRECT_NO_INPUT_DETAILS                         = 'Payment failed';
    const BAD_REQUEST_PAYMENT_CANNOT_REDIRECT                                   = 'Payment already processed';

    const BAD_REQUEST_SETTLEMENT_ANOTHER_QUEUE_OPERATION_IN_PROGRESS            = 'Request failed because another settlement queue operation in progress';
    const BAD_REQUEST_NO_DEFAULT_PLAN_IN_ORG                                    = 'No default plan id in org';

    const BAD_REQUEST_GATEWAY_DOWNTIME_CONFLICT                                 = 'A conflicting gateway downtime already exists.';

    // ---------------------- UPI (NPCI) Error codes -------------------------------
    const GATEWAY_ERROR_TRANSACTION_PENDING                                         = 'Transaction is in pending state';
    const GATEWAY_ERROR_REMITTER_CBS_OFFLINE                                        = 'Banking system is offline, please try after sometime.';
    const GATEWAY_ERROR_INACTIVE_DORMANT_BENEFICIARY_ACCOUNT                        = 'Beneficiary account is inactive or dormant';
    const GATEWAY_ERROR_BENEFICIARY_ACCOUNT_DOES_NOT_EXIST                          = 'Beneficiary account does not exist';
    const GATEWAY_ERROR_INVALID_BENEFICIARY_CREDENTIALS                             = 'Beneficiary credentials are invalid';
    const GATEWAY_ERROR_BENEFICIARY_ACCOUNT_BLOCKED                                 = 'Beneficiary account is blocked';
    const GATEWAY_ERROR_BENEFICIARY_COMPLIANCE_VIOLATION                            = 'Transaction cannot be completed. Compliance violation on beneficiary side';
    const GATEWAY_ERROR_BENEFICIARY_TRANSACTION_NOT_PERMITTED                       = 'Transaction not permitted to cardholder on beneficiary side';
    const GATEWAY_ERROR_BENEFICIARY_EXPIRED_CARD                                    = 'Transaction is declined at beneficiary side due to Expired card';
    const GATEWAY_ERROR_CREDIT_TIMEOUT                                              = 'Credit request is timed out';
    const GATEWAY_ERROR_CREDIT_FAILED                                               = 'Credit request is failed';
    const GATEWAY_ERROR_CREDIT_REVERSAL_TIMEOUT                                     = 'Credit reversal is timed out';
    const GATEWAY_ERROR_VALIDATION_ERROR                                            = 'Payment failed due to validation failure at bank or wallet gateway';
    const GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED                                   = 'Your payment didn\'t go through as it was declined by the bank. Try another payment method or contact your bank.';
    const GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT                         = 'Transaction failed due to insufficient funds.';
    const GATEWAY_ERROR_DO_NOT_HONOUR_BENEFICIARY                                   = 'Transaction processing declined on beneficiary side';
    const GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_REMITTER               = 'Suspected fraud or transaction is declined based on risk score by bank';
    const GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_BENEFICARY             = 'Suspected fraud or transaction is declined based on risk score by bank';
    const GATEWAY_ERROR_BANK_ACCOUNT_CREDIT_PROCESS_FAILED                          = 'Unable to process credit from bank\'s pool or bgl account';
    const GATEWAY_ERROR_BENEFICIARY_DUPLICATE_RRN_FOUND                             = 'Duplicate reference number found for the transaction on bank side.';
    const GATEWAY_ERROR_ISSUER_ACS_NOT_AVAILABLE                                    = 'Payment failed because cardholder couldn\'t be authenticated';
    const GATEWAY_ERROR_DEBIT_FAILED                                                = 'Payment failed. Please try again with another bank account.';
    const GATEWAY_ERROR_REVERSAL_ALREADY_SENT                                       = 'Payment failed. Reversal has already been sent';
    const GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE                                   = 'Your payment didn\'t go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.';

    // cardless emi error codes
    const BAD_REQUEST_PAYMENT_CARDLESS_EMI_CONTACT_MISMATCH                         = 'Contact given is invalid';
    const BAD_REQUEST_PAYMENT_CARDLESS_EMI_INVALID_PROVIDER                         = 'Cardless Emi provider is not supported';
    const BAD_REQUEST_EMI_DURATION_NOT_VALID                                        = 'Emi duration is not valid';
    const BAD_REQUEST_EMI_PLANS_DO_NOT_EXIST                                        = 'Emi plans do not exist';
    const BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST                              = 'Your payment was declined as you are not registered with the provider. To pay successfully try using another method.';
    const BAD_REQUEST_CARDLESS_EMI_INVALID_TOKEN                                    = 'Invalid token set for cardless emi';
    const BAD_REQUEST_CARDLESS_EMI_INVALID_MERCHANT_NAME                            = 'Invalid merchant name for cardless emi';
    const BAD_REQUEST_CARDLESS_EMI_INVALID_EMI_PLAN_ID                              = 'Invalid Emi plan id selected';
    const BAD_REQUEST_CARDLESS_EMI_MINIMUM_AMOUNT_REQUIRED                          = 'Amount less than minimum amount required';
    const BAD_REQUEST_CARDLESS_EMI_MAXIMUM_AMOUNT_LIMIT                             = 'Amount more than the maximum amount limit';
    const GATEWAY_ERROR_CARDLESS_EMI_PAYMENT_FAILED_PARTNER                         = 'Cardless Emi payment failed by the provider';
    const BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXHAUSTED                           = 'Cardless Emi credit limit of customer has exhausted';
    const BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_NOT_ACTIVATED                       = 'Cardless Emi credit limit of customer not activated';
    const BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_NOT_APPROVED                        = 'Cardless Emi credit limit of customer not approved';
    const BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXPIRED                             = 'Cardless Emi credit limit of customer has expired';

    const BAD_REQUEST_PAYLATER_USER_DOES_NOT_EXIST                                  = 'Your payment could not be completed as you are not registered with this payment provider. Try another payment method or contact your provider for details.';
    const GATEWAY_ERROR_PAYLATER_INVALID_TOKEN                                      = 'Payment failed due to technical error. Please try again with different provider/method';
    const BAD_REQUEST_PAYLATER_INVALID_MERCHANT_NAME                                = 'Merchant integration with the selected provider is incomplete. Please try again with different provider/method';
    const BAD_REQUEST_PAYLATER_MINIMUM_AMOUNT_REQUIRED                              = 'Amount less than minimum amount required';
    const BAD_REQUEST_PAYLATER_MAXIMUM_AMOUNT_LIMIT                                 = 'Amount more than the maximum amount limit';
    const GATEWAY_ERROR_PAYLATER_PAYMENT_FAILED_PARTNER                             = 'Payment failed by the provider';
    const BAD_REQUEST_PAYLATER_CREDIT_LIMIT_EXHAUSTED                               = 'Credit limit exhausted';
    const BAD_REQUEST_PAYLATER_CREDIT_LIMIT_NOT_ACTIVATED                           = 'Customer not activated';
    const BAD_REQUEST_PAYLATER_CREDIT_LIMIT_NOT_APPROVED                            = 'Credit limit of customer not activated';
    const BAD_REQUEST_PAYLATER_CREDIT_LIMIT_EXPIRED                                 = 'Customer credit limit expired.';


    const BAD_REQUEST_UPI_MPIN_NOT_SET                                              = 'Payment failed because UPI PIN is not set';
    const BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND                            = 'Registered Mobile number linked to the account has been changed/removed';
    const BAD_REQUEST_EXPIRED_VPA                                                   = 'Payment failed because VPA is marked as Expired';
    const BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT                                      = 'Payment failed because Account linked to VPA is invalid';
    const BAD_REQUEST_TRANSACTION_FREQUENCY_LIMIT_EXCEEDED                          = 'Payment failed because Transaction frequency limit has exceeded';
    const BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED                             = 'Payment failed because Transaction amount limit has exceeded';
    const BAD_REQUEST_TRANSACTION_NOT_ON_HOLD                                       = 'Only payment transactions that are put on hold can be released for settlement.';
    const BAD_REQUEST_FORBIDDEN_TRANSACTION_ON_VPA                                  = 'Payment failed because transactions are not allowed on this VPA';
    const BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED                        = 'Access to requested resource not available';

    const NODAL_BENEFICIARY_REGISTRATION_FAILED_RESPONSE                            = 'Beneficiary registration failed due to error';
    const BAD_REQUEST_PSP_DOESNT_EXIST                                              = 'Invalid VPA. Please enter a valid Virtual Payment Address';
    const BAD_REQUEST_PSP_ERROR                                                     = 'Payment failed at PSP';
    const BAD_REQUEST_UPI_INVALID_DEVICE_FINGERPRINT                                = 'Payment Failed due to issue with your UPI App. Please try again with another app or payment method';
    const BAD_REQUEST_PAYMENT_UPI_RESTRICTED_VPA                                    = 'Invalid VPA. Please enter a valid Virtual Payment Address';
    const BAD_REQUEST_PAYMENT_UPI_DEVICE_MISSING                                    = 'UPI device must be present';
    const BAD_REQUEST_PAYMENT_UPI_MOBILE_NUMBER_MAPPED_TO_MULTIPLE_CUSTOMERS        = 'Mobile number registered with multiple customers';
    const BAD_REQUEST_DUPLICATE_REQUEST                                             = 'The request is duplicate';
    const BAD_REQUEST_PAYMENT_UPI_DEBIT_AND_CREDIT_SAME_ACCOUNT                     = 'The debit and credit for the transaction is done on the same account';
    const BAD_REQUEST_INVALID_ACCOUNT_TYPE_PASSED_FOR_MODE                          = 'The mode is not valid for the given account type';
    const BAD_REQUEST_INVALID_AMOUNT_PASSED_FOR_ACCOUNT_TYPE                        = 'Amount exceeds the max amount for the given account type';

    const BAD_REQUEST_PARTNER_ID_SENT_FOR_PURE_PLATFORM                             = 'Application id needs to be sent for pure platform instead of partner id';
    const BAD_REQUEST_APPLICATION_ID_OR_PARTNER_ID_MISSING                          = 'Application id or Partner id is required';
    const BAD_REQUEST_APPLICATION_ID_PARTNER_ID_BOTH_PRESENT                        = 'Application id and Partner id both sent in the request';
    const BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS                         = 'Application/submerchant config already exists';
    const BAD_REQUEST_EXPIRY_DATE_SET_FOR_SUBVENTION                                = 'Expiry date should not be set for subvention model';
    const BAD_REQUEST_PARTNER_CONFIGURATION_INVALID                                 = 'The partner configuration is invalid';
    const BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID                     = 'The partner sub merchant configuration is invalid';
    const BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST                                    = 'The merchant id does not exist or invalid';
    const BAD_REQUEST_PARTNER_ID_DOES_NOT_EXIST                                     = 'The partner id does not exist or invalid';

    const BAD_REQUEST_PAYMENT_CANNOT_REDIRECT_TO_AUTHORIZE                          = 'Payment failed';

    const BAD_REQUEST_RECURRING_TOKEN_EXPIRED                                       = 'Token has expired and cannot be used for recurring payments';

    const BAD_REQUEST_MERCHANT_ID_NOT_PRESENT                                       = 'Invalid Merchant Id';

    // Instant refunds
    const BAD_REQUEST_INSTANT_REFUND_NOT_SUPPORTED                                  = 'Instant refund not supported for the payment';

    // Banking Accounts
    const BAD_REQUEST_ERROR_BANKING_ACCOUNT_FUND_ACCOUNT_CREATION_FAILED           = 'Operation could not be completed. Please try again';
    const BAD_REQUEST_ERROR_DIRECT_FUND_ACCOUNT_AND_SOURCE_ACCOUNT_CREATION_FAILED = 'Operation could not be completed. Please try again';
    const BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED                      = 'Operation could not be completed. Please try again';
    const BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_NOT_PERMITTED                     = 'Account cannot be activated, Please contact support';
    const BAD_REQUEST_ERROR_WRONG_BANKING_ACCOUNT_CREDENTIALS                      = 'Account details are incorrect. Please try again';
    const BAD_REQUEST_BANKING_ACCOUNT_ALREADY_ACTIVATED                            = 'Operation failed, your account is already activated';
    const BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_PERMITTED_ONLY_ON_ADMIN_AUTH      = 'Access forbidden for requested resource';
    const BAD_REQUEST_BANKING_ACCOUNT_WEBHOOK_RESET_NOT_ALLOWED_FOR_CURRENT_STATUS = 'Webhook data reset not allowed for current status';

    // stork
    const BAD_REQUEST_STORK_WEBHOOK_ALREADY_CREATED                                 = 'Webhook setting already exists';

    // Excel Store
    const BAD_REQUEST_EXCEL_STORE_FILE_PARAM                                        = 'File field should of type file';

    const BAD_REQUEST_BAS_INVALID_BUSINESS_ID                                       = 'Invalid business id provided';

    const BAD_REQUEST_BAS_BUSINESS_ID_NOT_CREATED                                   = 'Business creation is prerequisite for the request';

    const BAD_REQUEST_BAS_BUSINESS_ALREADY_CREATED                                  = 'Business already created for the merchant';

    const BAD_REQUEST_BAS_BUSINESS_DELETION_OPERATION_NOT_PERMITTED                 = 'Business deletion operation is not allowed';

    const BAD_REQUEST_BAS_PERSON_API_FAILURE                                        = 'Error while creating or updating a person';

    const BAD_REQUEST_BANKING_ACCOUNT_SERVICE_ERROR                                 = 'Error from banking account service';

    const BAD_REQUEST_BAS_CRON_PATH_MISSING                                         = 'Banking account service cron uri is not provided';
    const BAD_REQUEST_BAS_PATH_MISSING                                              = 'Banking account service uri is not provided';

    //Offer
    const OFFER_MAX_CARD_USAGE_LIMIT_EXCEEDED                                       = 'Offer Maximum Card Usage limit exceeded';
    const OFFER_MAX_OFFER_LIMIT_EXCEEDED                                            = 'Offer Maximum Usage limit exceeded';
    const OFFER_PAYMENT_METHOD_NOT_AVAILABLE                                        = 'Payment Method is not available for this Offer';
    const OFFER_CARD_TYPE_DOES_NOT_MATCH                                            = 'Card type entered does not match the Offers parameters';
    const OFFER_PAYMENT_NETWORK_NOT_AVAILABLE                                       = 'Offer Payment Method Network is not same as Selected Payment Method Network';
    const OFFER_EMI_DURATION_NOT_SAME                                               = 'Offer Emi duration is not same as Selected Emi duration';
    const OFFER_CARD_INTERNATIONAL                                                  = 'Selected Card is not international but offer applied requires international card';
    const OFFER_IINS_DOES_NOT_MATCH                                                 = 'Selected card does not belong to offer iins';
    const OFFER_WALLET_NOT_SAME                                                     = 'Offer Wallet does not match with payment wallet';
    const OFFER_PERIOD_NOT_ACTIVE                                                   = 'Offer Applied is not active for this time period';
    const OFFER_ORDER_AMOUNT_LESS_OFFER_MIN_AMOUNT                                  = 'Order Amount is less than Offer Minimum amount';
    const OFFER_ORDER_AMOUNT_GREATER_OFFER_MAX_AMOUNT                               = 'Order Amount is greater than Offer Maximum amount';
    const OFFER_NOT_ACTIVE                                                          = 'Offer is inactive';
    const OFFER_NOT_APPLICABLE_ON_ISSUER                                            = 'Offer not applicable on selected issuer';
    const OFFER_ORDER_AMOUNT_LESS_PROVIDER_MIN_TRANSACTION_AMOUNT                   = 'Order amount is less than Provider Minimum Transaction Amount';

    const BAD_REQUEST_D2C_WRONG_OTP                                                 = 'Wrong OTP, Make sure to enter the correct OTP.';
    const BAD_REQUEST_D2C_NON_OWNER_USER_NOT_ALLOWED                                = 'Access denied.';
    const BAD_REQUEST_D2C_CREDIT_BUREAU_NO_RECORDS_FOUND                            = 'No records found. Please contact support.';
    const BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT                    = 'No records found for this phone number.';
    const BAD_REQUEST_D2C_MANDATORY_FIELD_MISSING                                   = 'Mandatory field missing';
    const BAD_REQUEST_D2C_INVALID_DATA_IN_INPUT                                     = 'Invalid Data in input';
    const BAD_REQUEST_D2C_INVALID_PAN                                               = 'Invalid PAN';
    const SERVER_ERROR_D2C_VOUCHER_CODE_INVALID                                     = 'Something went wrong. Please try again later';
    const GATEWAY_ERROR_D2C_EXPERIAN_VALIDATION_FAILURE                             = 'Something went wrong. Please try again later';
    const GATEWAY_ERROR_D2C_EXPERIAN_SERVER_ERROR                                   = 'Something went wrong. Please try again later';
    const GATEWAY_ERROR_D2C_EXPERIAN_SYSTEM_ERROR                                   = 'Something went wrong. Please try again later';
    const GATEWAY_ERROR_D2C_EXPERIAN_INVALID_AFFINITY                               = 'Something went wrong. Please try again later';


    // Early Settlements and payouts
    const BAD_REQUEST_ES_ON_DEMAND_IMPS_AMOUNT_LIMIT_EXCEEDED                       = 'Please provide an amount less than 2 Lacs to get a settlement at this point of time.';

    //FTS
    const BAD_REQUEST_UNSUPPORTED_SOURCE_TYPE                                       = 'Unsupported source type';
    const BAD_REQUEST_ERROR_SOURCE_ACCOUNT_FUND_ACCOUNT_CREATION_FAILED             = 'Fund Account could not be created for source account';

    //Invalid Bank Account
    const BAD_REQUEST_INVALID_BANK_ACCOUNT                                          =  'The bank account entered is invalid';
    const BAD_REQUEST_VIRTUAL_BANK_ACCOUNT                                          = 'The bank account entered is Virtual Account';

    //Invalid Product Name
    const BAD_REQUEST_INVALID_PRODUCT_NAME                                          = 'The product requested is invalid';
    const BAD_REQUEST_INVALID_STATUS_TRANSITION                                     = 'The status transition is invalid';
    const BAD_REQUEST_PRODUCT_INTERNATIONAL_REQUIRED                                = 'The product international field cant be empty';
    const BAD_REQUEST_INTERNATIONAL_ENABLEMENT_INVALID_STATUS                       = 'Invalid status for international enablement';
    const BAD_REQUEST_INTERNATIONAL_ENABLEMENT_NO_ENTRY_FOUND                       = 'No previous activity found for international enablement.';
    const BAD_REQUEST_INTERNATIONAL_ENABLEMENT_DISCARD                              = 'No In Progress activity found for international enablement.';
    const INVALID_DATA_PARSER                                                       = 'Invalid Data Parser Requested';
    const WORKFLOW_CREATION_FAILURE                                                 = 'Workflow Creation Failed';
    const BAD_REQUEST_INVALID_PERMISSION                                            = 'The permission for current workflow is invalid.';
    const BAD_REQUEST_PRODUCT_INTERNATIONAL_CANT_BE_ENABLED                         = 'Cant Enable Product International for the merchant';

    const BAD_REQUEST_PAYMENT_CONFIG_MARKED_FOR_REFUND                              = 'The Payment has been marked for refund in payment config';

    // Upi Mandates
    const BAD_REQUEST_UPI_MANDATE_END_TIME_INVALID                                  = 'Invalid end time for upi mandate payment, end time must be greater than start time and current time';
    const BAD_REQUEST_UPI_MANDATE_TIME_RANGE_REQUIRED                               = 'Start time and end time is required in case of upi mandate payments';
    const BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL                                      = 'Token invalid, cannot be revoked';
    const BAD_REQUEST_INVALID_TOKEN_FOR_PAUSE                                       = 'Token invalid, cannot be paused';
    const BAD_REQUEST_INVALID_TOKEN_FOR_RESUME                                      = 'Token invalid, cannot be resumed';
    const BAD_REQUEST_CUSTOMER_TOKEN_COUNT_NOT_EQUAL                                = 'One or more tokens do not belong to this customer';
    const BAD_REQUEST_PAYMENT_OTP_VALIDATION_INVALID_LENGTH                         = 'You’ve entered an incorrect OTP. Please enter an OTP of length between 4-10 digits.';

    const BAD_REQUEST_ANOTHER_PROMOTION_EVENT_ALREADY_EXISTS                        = 'Bad request, another event exists with same name';
    const BAD_REQUEST_UPI_MANDATE_INVALID_EXECUTION_TIME                            = 'Execution only allowed between start time and end time';
    const BAD_REQUEST_UPI_END_TIME_OUT_OF_RANGE                                     = 'End time provided for upi mandate is out of range';
    const BAD_REQUEST_UPI_MANDATE_INTENT_NOT_SUPPORTED                              = 'Intent flow is not supported for upi mandates.';
    const BAD_REQUEST_PAYMENT_UPI_MANDATE_REVOKED                                   = 'Mandate has already been revoked.';
    const BAD_REQUEST_PAYMENT_UPI_MANDATE_EXPIRED                                   = 'UPI Mandate is expired.';
    const BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED                                  = 'Payment was unsuccessful as an error occurred at the UPI app. Any amount deducted will be refunded within 5-7 working days.';
    const BAD_REQUEST_PAYMENT_UPI_MANDATE_NOT_AVAILABLE_ON_VPA                      = 'UPI Mandate not available on VPA.';
    const BAD_REQUEST_PAYMENT_UPI_MANDATE_NOT_REVOKABLE                             = 'UPI Mandate cannot be revoked';
    const BAD_REQUEST_PAYMENT_UPI_MANDATE_AUTO_CAPTURE_NOT_ALLOWED                  = 'Auto capture is not allowed for upi mandates.';
    const BAD_REQUEST_NEGATIVE_BALANCE_BREACHED                                      = 'Your Negative Balance Limit has reached its maximum. Please Add funds to your account.';
    const RESERVE_BALANCE_CREATE_ALREADY_IN_PROGRESS                                 = 'Reserve Balance creation is already in progress';
    const BAD_REQUEST_RESERVE_BALANCE_TICKET_NOT_FOUND                               = 'Reserve Balance creation ticket not found';
    const FRESHDESK_TICKET_ALREADY_EXISTS                                            = 'Freshdesk ticket for Reserve Balance creation already exists';
    const BAD_REQUEST_USER_OPT_OUT_WHATSAPP_NOTIFICATION                             = 'Requested User has opt out whats app notifications';
    const BAD_REQUEST_FRESHDESK_TICKET_NOT_FOUND                                     = 'Freshdesk ticket not found';
    const BAD_REQUEST_CUSTOMER_TICKET_FETCH_FAILED                                   = 'Failed to fetch customer tickets';
    const BAD_REQUEST_NO_TICKETS_FOUND_FOR_CUSTOMER                                  = 'No ticket found for the given customer';
    const SERVER_ERROR_FRESHDESK_AGENT_NOT_FOUND                                     = 'No agent found for given agent id';
    const BAD_REQUEST_FRESHDESK_TICKET_UPDATE_FAILED                                 = 'Failed to update freshdesk ticket';
    const BAD_REQUEST_FRESHDESK_TICKET_ALREADY_CLOSED                                = 'Bad request, the ticket is already closed';
    const BAD_REQUEST_OPEN_TICKETS_LIMIT_EXCEEDED                                    = 'Bad request open tickets limit exceeded';
    const BAD_REQUEST_BALANCE_CONFIG_ALREADY_EXISTS_FOR_BALANCE                      = 'Balance Config already exists for this Balance';
    const BAD_REQUEST_BALANCE_CONFIG_INVALID_NEGATIVE_LIMIT                          = 'Invalid negative limit for Balance Config creation';
    const BAD_REQUEST_BALANCE_CONFIG_INVALID_NEGATIVE_TRANSACTION_FLOW               = 'Invalid transaction flows for Balance Config creation';

    const BAD_REQUEST_MERCHANT_REQUESTED_BANK_ACCOUNT_SAME_AS_CURRENT_BANK_ACCOUNT   = 'Merchant requested bank account is same as the current active bank account';

    const BAD_REQUEST_LOW_BALANCE_CONFIG_ALREADY_EXISTS_FOR_ACCOUNT_NUMBER           = 'Low balance config already exists for account number';
    const BAD_REQUEST_LOW_BALANCE_CONFIG_IS_NOT_SUPPORTED_IN_TEST_MODE               = 'Low balance config is not supported in test mode';
    const BAD_REQUEST_LOW_BALANCE_CONFIG_AUTH_NOT_SUPPORTED                          = 'Invalid parameters for current auth.';
    const BAD_REQUEST_LOW_BALANCE_CONFIG_ENABLE_DISABLE_ADMIN_AUTH_ONLY              = 'Following action cannot be performed via dashboard.';
    const BAD_REQUEST_LOW_BALANCE_CONFIG_DELETE_NOT_ALLOWED                          = 'Delete operation is not allowed on low balance configs of autoload_balance type.';
    const BAD_REQUEST_LOW_BALANCE_CONFIG_INVALID_TYPE                                = 'Invalid Low Balance Config type.';

    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MODE                                              = 'Incorrect mode provided.';
    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MOBILE_NUMBER                                     = 'Incorrect mobile numbers provided.';
    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_MODE                                   = 'A merchant notification config already exists for the given mode.';
    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_ALREADY_EXISTS_FOR_NOTIFICATION_TYPE                      = 'A merchant notification config already exists for the given notification type.';
    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_LOWER_THRESHOLD_GREATER_THAN_UPPER_THRESHOLD              = 'The lower threshold is greater than the upper threshold.';
    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD   = 'The new upper threshold is lower than the existing lower threshold.';
    const BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_LOWER_THRESHOLD_GREATER_THAN_EXISTING_UPPER_THRESHOLD = 'The new lower threshold is greater than the existing upper threshold.';

    const BAD_REQUEST_X_CREDITS_SUPPORTED_IN_ONLY_LIVE_MODE                          = 'Bad request, X credits supported in only live mode';

    //Using double quotes to escape newline for cred
    const BAD_REQUEST_CRED_CUSTOMER_NOT_ELIGIBLE                                    = "You are not eligible to pay via CRED as you are not a CRED member.\n Please use another method to complete your payment.";
    const BAD_REQUEST_MISSING_HEADERS                                               = 'It seems that there are some details missing in the request for this API call.';
    const BAD_REQUEST_INVALID_TRACKING_ID                                           = 'The order for which you are trying to place a request does not exist in our system. Request is being made via an incorrect tracking ID.';
    const BAD_REQUEST_PAYMENT_DUPLICATE_REQUEST                                     = 'The request for this tracking id is in progress already.';
    const GATEWAY_ERROR_PAYMENT_REFUND_FAILED                                       = 'The refund is being request for an order which is in progress. Please initiate a refund request once the order is complete.';
    const GATEWAY_ERROR_REFUND_AMOUNT_GREATER_THAN_CAPTURED                         = 'The refund amount being requested is higher than the original order amount. Hence this refund cannot be processed.';
    const GATEWAY_ERROR_INTERNAL_SERVER_ERROR                                       = 'Something went wrong. Please try again or use another method to complete your payment';
    const BAD_REQUEST_CRED_MISSING_FIELDS_MESSAGE                                   = 'Something went wrong. Please try again or use another method to complete your payment';
    const BAD_REQUEST_CRED_USER_NOT_REGISTERED                                      = "You are not eligible to pay via CRED as you are not a CRED member.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_PENDING_USER                                             = "You are not eligible to pay via CRED as you are not a CRED member.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_CARD_NOT_VERIFIED                                        = "You do not have a verified card on CRED to use this payment method.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_WAITLISTED_USER                                          = "You are not eligible to pay via CRED as you are not a CRED member.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_UNSUPPORTED_APP_VERSION                                  = "You do not have the latest CRED app version to make this payment via CRED.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_NO_SUPPORTED_APP_VERSION                                 = "You do not have the latest CRED app version to make this payment via CRED.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_INACTIVE_USER                                            = "You are not eligible to pay via CRED as you are not a CRED member.\n Please use another method to complete your payment.";
    const BAD_REQUEST_CRED_BLOCKED_USER                                             = 'You CRED account is blocked. Please reach out to support@cred.club to unblock your account and enable this payment method';
    const GATEWAY_ERROR_CRED_RESERVED_ERROR                                         = 'Download CRED app and activate your membership to unlock this payment option and avail exclusive discounts.';
    const BAD_REQUEST_DISCOUNT_GREATER_THAN_BASE_AMOUNT                             = 'Discount amount greater than base amount';
    const BAD_REQUEST_PAYMENT_CRED_NOT_ENABLED_FOR_MERCHANT                         = 'CRED payment method is not enabled. Please complete your payment via other payment mode.';
    const BAD_REQUEST_PAYMENT_APP_NOT_ENABLED_FOR_MERCHANT                          = 'Payment method is not enabled. Please complete your payment via other payment mode.';

    // nach signed form upload
    const BAD_REQUEST_NACH_UNKNOWN_FILE_TYPE                                        = 'The file type of the image is not supported';
    const BAD_REQUEST_NACH_FILE_SIZE_EXCEEDS_LIMIT                                  = 'The file size exceeds the permissible limits';
    const BAD_REQUEST_NACH_IMAGE_NOT_CLEAR                                          = 'The uploaded image is not clear. This can either be due to poor resolution or because part of the image is cropped';
    const BAD_REQUEST_NACH_FORM_SIGNATURE_IS_MISSING                                = 'The signature of the customer is either missing or could not be detected';
    const BAD_REQUEST_NACH_FORM_MISMATCH                                            = 'The ID of the uploaded form does not match with that in our records';
    const BAD_REQUEST_NACH_FORM_DATA_MISMATCH                                       = 'One or more of the fields on the NACH form do not match with that in our records';
    const BAD_REQUEST_NACH_FORM_STATUS_PENDING                                      = 'A form against this order is pending action on the destination bank. A new form cannot be submitted till a status is received';

    const BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INCORRECT_BALANCE_TYPE                = 'Only Banking type balance is allowed.';
    const BAD_REQUEST_FREE_PAYOUTS_ATTRIBUTES_INVALID_BALANCE_ID                    = 'Invalid balance id, no db records found.';
    const BAD_REQUEST_VIRTUAL_ACCOUNT_RECEIVER_TYPES_NOT_ALLOWED                    = 'Third Party Validation is not supported for UPI Transfers.';

    const BAD_REQUEST_PAYOUT_SOURCE_ALREADY_EXISTS                                  = 'A source with same source_id and source_type already exists.';
    const BAD_REQUEST_ANOTHER_PAYOUT_SOURCE_EXISTS_WITH_SAME_PRIORITY               = 'A source with same priority already exists.';
    const BAD_REQUEST_PG_ROUTER_ONLY_LIVE_MODE_SUPPORTED                            = 'Only live mode orders are supported for sync';

    const VENDOR_CONNECTION_ERROR                                                   = 'Connection to vendor failed';
    const BAD_REQUEST_COMPANY_SEARCH_RETRIES_EXHAUSTED                              = 'Company Search retries exhausted';

    const BAD_REQUEST_LAXMI_VILAS_BANK_PAYMENT_DISABLED                             = 'We are unable to complete this transaction due to the restrictions on Laxmi Vilas Bank\'s operations by RBI (Gazette notification (S.O. 4127(E)) dated 17th November 2020';
    const BAD_REQUEST_INVALID_EMAIL_TYPE                                            = 'Invalid email type.';

    const BAD_REQUEST_PAYMENT_FAILED_BY_AVS                                         = 'Payment failed as it did not pass all risk checks. Please try again or use another card.';

    const BAD_REQUEST_SETTLEMENT_NOT_FOUND                                          = 'Settlement not found';

    // Accounts/ stakeholder V2 document upload error descriptions
    const BAD_REQUEST_DOCUMENT_UPLOAD_OPERATION_IN_PROGRESS                         = 'Document upload already in progress';
    const BAD_REQUEST_INVALID_FILE_ACCESS                                           = 'Invalid file id provided or merchant is unauthorized to access the fileId(s) provided';
    const BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_FIELDS_ARE_ALLOWED                   = 'Only fields requested for needs clarification are allowed for update';
    const BAD_REQUEST_ONLY_NEEDS_CLARIFICATION_DOCUMENTS_ARE_ALLOWED                = 'Only documents requested for needs clarification are allowed for upload';
    const BAD_REQUEST_ONLY_REMAINING_KYC_FIELDS_ARE_ALLOWED                         = 'You can not update this value as it is already verified.';
    const BAD_REQUEST_MERCHANT_PRODUCT_CONFIG_DOESNT_EXIST                          = 'The provided product config id doesnt exist for the merchant';

    const BAD_REQUEST_OFFER_SUBSCRIPTION_NOT_ENABLED                                = 'Offers On Subscription not enabled for the merchant';
    const BAD_REQUEST_OFFER_SUBSCRIPTION_PAYLOAD_ABSENT                             = 'Subscription data not present in Offer';

    const BAD_REQUEST_STAKEHOLDER_DOES_NOT_BELONG_TO_MERCHANT                       = 'Stakeholder does not belong to merchant';
    const BAD_REQUEST_INVALID_FILE_IDS_PROVIDED                                     = 'Invalid file ids provided';
    const BAD_REQUEST_DCC_CONFIG_PRESENT                                            = 'Dcc Config is already present for the provided merchant';

    const BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST                       = 'Must not be able to make live request when not activated';
    const BAD_REQUEST_SUBSCRIPTION_OFFER_METHOD_MISMATCH                            = 'Payment method does not match with offer payment method';

    const BAD_REQUEST_INVALID_SESSION_ID    = "BAD_REQUEST_INVALID_SESSION_ID";

    //TPV - Third party validation
    //- validations on source accounts through which money gets loaded to va.
    const BAD_REQUEST_REJECTED_TPV_WITHOUT_REMARKS                                  = 'Remarks should be provided for rejected status';
    const BAD_REQUEST_DUPLICATE_TPV                                                 = 'TPV already exists for the corresponding merchantId, balance and account number';
    const BAD_REQUEST_TPV_NOT_EXISTS                                                = 'No TPV found with the entered details';

    const BAD_REQUEST_TPV_CREATE_OPERATION_IN_PROGRESS                              = 'Request failed because another request is in progress with the same merchant id';

    const BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID                               = 'Balance id does not belong to the merchant';

    const BAD_REQUEST_TPV_BALANCE_TYPE_DIRECT_NOT_SUPPORTED                         = 'Balance type direct is not supported in tpv';

    const BAD_REQUEST_TPV_PRIMARY_BALANCE_NOT_SUPPORTED                             = 'Primary balance is not supported in tpv';

    const BAD_REQUEST_TPV_ERROR                                                     = 'Error in processing the tpv request';

    const BAD_REQUEST_FUND_LOADING_REFUND_PAYOUT_CREATION_FAILED                    = 'Error in creating refund payout for failed fund loading attempt';

    // Templating Service
    const BAD_REQUEST_ERROR_IN_TEMPLATING_RESPONSE                                  = 'Received an Error Response from templating service';
    const BAD_REQUEST_ENTITY_ALREADY_EXISTS                                         = 'Provided entity already exists. Please try again with other details.';

    // NPS Survey
    const BAD_REQUEST_DUPLICATE_SURVEY_TYPE                                         = 'Survey with same type already exists';
    const BAD_REQUEST_INVALID_SURVEY_TYPE                                           = 'Invalid survey type';
    const BAD_REQUEST_NPS_SURVEY_NOT_APPLICABLE_IN_TEST_MODE                        = 'Nps survey not applicable in test mode';

    // Wallet Account AMazon Pay for X
    const BAD_REQUEST_WALLET_ACCOUNT_FUND_ACCOUNT_CREATION_NOT_PERMITTED            = 'Creating a Fund Account of wallet type is not permitted';
    const BAD_REQUEST_AMAZONPAY_PAYOUTS_NOT_PERMITTED                               = 'Payouts to amazonpay wallet are not permitted';

    const BAD_REQUEST_FTA_CREATION_FOR_PAYOUT_SERVICE_IN_PROGRESS                   = 'Request failed because another fta creation for the same request is in progress';
    const BAD_REQUEST_LEDGER_CREATION_FOR_PAYOUT_SERVICE_IN_PROGRESS                = 'Request failed because another ledger creation for the same request is in progress';
    const BAD_REQUEST_REVERSAL_CREATION_FOR_PAYOUT_SERVICE_IN_PROGRESS              = 'Request failed because another reversal creation for the same request is in progress';

    const BAD_REQUEST_NON_EXISTING_QR_CODE_ID                                       = 'QR Code Id provided doesn\'t exist';
    const BAD_REQUEST_MERCHANT_TNC_NOT_APPLICABLE                                   = 'Request for creation of Merchant TnC failed because it is not applicable to the current merchant';
    const BAD_REQUEST_MERCHANT_NOT_ELIGIBLE_FOR_1CC                                 = 'Request failed because the merchant is not eligible for 1CC product';

    // admin login 2fa
    const BAD_REQUEST_ADMIN_2FA_LOGIN_OTP_REQUIRED                                  = 'Bad request admin 2fa login otp required';
    const BAD_REQUEST_LOCKED_ADMIN_LOGIN                                            = 'Bad request locked admin login';


    // Onboarding APIs
    const BAD_REQUEST_WALLET_INSTRUMENT_INVALID                                     = 'Invalid wallet instrument code';
    const BAD_REQUEST_UPI_INSTRUMENT_INVALID                                        = 'Invalid upi instrument code';
    const BAD_REQUEST_BANK_INSTRUMENT_INVALID                                       = 'Invalid bank instrument code';
    const BAD_REQUEST_PAYLATER_INSTRUMENT_INVALID                                   = 'Invalid paylater instrument code';
    const BAD_REQUEST_EMI_INSTRUMENT_INVALID                                        = 'Invalid emi instrument code';
    const BAD_REQUEST_PAYMENT_CUSTOMER_DROPPED_OFF                                  = 'Customer dropped off without completing the payment';
    const BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE                   = 'International enablement details couldn\'t be captured due to validation failure.';
    const BAD_REQUEST_FETCH_LOGO_FROM_URL_FAILED                                    = 'Error occurred while fetching logo from url provided';
    const BAD_REQUEST_TNC_ACCEPTANCE_NOT_PRESENT_FOR_MERCHANT                       = 'Tnc acceptance not present for the merchant';

    // Upi Payment Service
    const SERVER_ERROR_UPI_PAYMENT_SERVICE_REQUEST_ERROR                            = 'We are facing some trouble completing your request at the moment. Please try again shortly.';
    const SERVER_ERROR_UPI_PAYMENT_SERVICE_REQUEST_TIMEOUT                          = 'We are facing some trouble completing your request at the moment. Please try again shortly.';
    const SERVER_ERROR_UPI_PAYMENT_SERVICE_CONNECTION_FAILED                        = 'We are facing some trouble completing your request at the moment. Please try again shortly.';

    const BAD_REQUEST_MERCHANT_ID_IS_REQUIRED                                       = 'Merchant Id is a required param';

    const BAD_REQUEST_ENCRYPTED_COMMENT_NOT_FOUND                                   = 'No Credentials found';

    // Partner Kyc Access
    const BAD_REQUEST_KYC_ACCESS_ALREADY_APPROVED                                   = 'Request failed as kyc access already approved';
    const BAD_REQUEST_KYC_ACCESS_ALREADY_REJECTED                                   = 'Request failed as kyc access already rejected';

    // Payouts Batch
    const BAD_REQUEST_PAYOUTS_BATCH_NOT_ALLOWED = 'Creating a payouts batch request is not allowed. Please contact support for more details.';

    //2FA with password error codes
    const BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD                                  = 'Verification failed because of incorrect password.';
    const BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED                              = 'Second factor authentication is enabled for user. Password is required';
    const BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED                                  = '2FA with password suspended due to too many incorrect attempts. Please try after some time.';
    const SERVER_ERROR_2FA_INCORRECT_PASSWORD_REDIS_ERROR                           = 'An error occurred with redis during 2fa with password flow.';
    const BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED                                  = 'Please use your email address to login.';
    // Email/Mobile + OTP Signup
    const BAD_REQUEST_EMAIL_ALREADY_EXISTS                                          = 'That email is already taken.';
    const BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS                                 = 'That phone number is already taken.';
    const BAD_REQUEST_EMAIL_SIGNUP_OTP_SEND_THRESHOLD_EXHAUSTED                     = 'You have exceeded the maximum attempts for resending OTP. Please try after sometime.';
    const BAD_REQUEST_REDIS_KEY_THRESHOLD_EXCEEDED                                  = 'Bad request, redis key threshold exceeded.';
    const BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED                   = 'This account is suspended since you have exceeded maximum attempts for incorrect OTP. Please try again later.';
    const BAD_REQUEST_EMAIL_ASSOCIATED_WITH_ANOTHER_ACCOUNT                         = 'The email is already associated with another account, please give a new email';
    const BAD_REQUEST_LIMIT_FOR_UPDATE_CONTACT_MOBILE_EXCEEDED                      = 'Contact number cannot be updated. Please reach out to the support team to get it updated';
    const BAD_REQUEST_USERNAME_MUST_BE_DIFFERENT                                    = 'User name must be different from existing name';

    const BAD_REQUEST_CHANGE_PASSWORD_THRESHOLD_EXHAUSTED                           = 'Password change suspended due to too many requests. Please try after some time.';

    const PRE_SIGNUP_EMAIL_NOT_ALLOWED                                              = 'Email not acceptable here when signed up with email.';
    const PRE_SIGNUP_CONTACT_MOBILE_NOT_ALLOWED                                     = 'Contact mobile not acceptable here when signed up with contact mobile.';

    const BAD_REQUEST_MERCHANT_NOT_FOUND                                            = 'Merchant not found/Invalid Merchant Id';
    const BAD_REQUEST_KEYS_REGENERATED_PREVIOUSLY                                   = 'Merchant has already generated keys within 24 hours';

    const BAD_REQUEST_STANDALONE_PAYOUT_TO_CARDS_NOT_ALLOWED                        = 'Standalone payouts API is not supported for payouts to card numbers, please use the composite API';
    const BAD_REQUEST_MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS              = 'Payout mode is not supported for tokenised cards';
    const BAD_REQUEST_CUSTOMER_ADDRESS_NOT_FOUND                                    = 'Address not found/Invalid Address Id';

    const BAD_REQUEST_TDS_AMOUNT_GREATER_THAN_PAYOUT_AMOUNT                         = 'TDS amount cannot be greater than Payout amount';
    const BAD_REQUEST_INVALID_TDS_CATEGORY_ID                                       = 'Invalid TDS Category';
    const BAD_REQUEST_UPDATE_PAYOUT_ATTACHMENTS                                     = 'Invalid Update Payout Attachments request';
    const BAD_REQUEST_INVALID_PAYOUT_SOURCE_FOR_UPDATE                              = 'Invalid Payout Source for Update';
    const BAD_REQUEST_ATTACHMENT_NOT_LINKED_TO_PAYOUT                               = 'Attachment not linked to payout';
    const SERVER_ERROR_TAX_PAYMENT_ID_UPDATE_FAILURE                                = 'Failed to update tax payment ID for payout';
    const SERVER_ERROR_INVALID_UFH_CLIENT                                           = 'Failed to create UFH Client';
    const SERVER_ERROR_ATTACHMENT_UPDATE_FAILURE                                    = 'Failed to update attachment for payout';
    const SERVER_ERROR_ATTACHMENT_GET_FAILURE                                       = 'Failed to get attachment for payout';
    const BAD_REQUEST_PAYOUT_ATTACHMENT_NOT_ALLOWED_FOR_THIS_ROLE                   = 'Update not allowed for the user role in this state';
    const BAD_REQUEST_INVALID_ATTACHMENT_SIZE                                       = 'File size greater than 5MB cannot be uploaded';
    const BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_TDS                        = 'Payout with TDS not supported via private auth';
    const BAD_REQUEST_AUTH_NOT_SUPPORTED_FOR_PAYOUT_WITH_ATTACHMENTS                = 'Payout with attachments not supported via private auth';
    const BAD_REQUEST_INVALID_TAX_PAYMENT_ID                                        = 'Invalid tax_payment_id';
    const SERVER_ERROR_GET_ATTACHMENTS_FAILURE                                      = 'Failed to get attachments for the payouts';
    const BAD_REQUEST_OAUTH_ROLE_NOT_FOUND                                          = 'OAuth Role not found';

    const BAD_REQUEST_SET_DEFAULT_METHODS_ALREADY_IN_PROGRESS                       = 'Set Default Methods is in progress for the merchant';

    const BAD_REQUEST_FILE_HASH_MISSING_FOR_ATTACHMENT                              = 'file_hash missing for attachment';
    const BAD_REQUEST_INVALID_FILE_HASH_FOR_ATTACHMENT                              = 'Invalid file_hash for attachment';

    const BAD_REQUEST_ENABLE_NON_3DS_REQUEST_MADE_IN_LAST_30_DAYS                   = 'Bad request enable non-3ds card processing request made in last 30 days';

    const BAD_REQUEST_OTP_VERIFICATION_LOG                                          = 'Contact mobile must be same as merchant business mobile number';
    const BAD_REQUEST_OTP_NOT_REQUIRED                                              = 'otp is/are not required and should not be sent';

    const BAD_REQUEST_SUBMERCHANT_UNLINKING_FAILED                                  = 'Aggregate Settlement submerchant unlinking failed';

    const EMERCHANTPAY_INTERNATIONAL_DISABLED_DESC                                  = "Payment method request not allowed as international is disabled on the merchant";
    const EMERCHANTPAY_INSTRUMENT_INVALID_DESC                                      = "Invalid payment method requested";
    const BAD_REQUEST_RAZORPAY_WALLET_ERROR                                         = "failed to perform razorpay wallet transaction.";
    const SERVER_ERROR_INVALID_ACS_EVENT_PROCESSOR                                  = "invalid acs event processor attached to acs event processor factory";

    const BAD_REQUEST_ROUTE_NOT_ACCESSIBLE_VIA_BANKING                              = 'Route not enabled for banking';

    const BAD_REQUEST_INVALID_INPUT_FOR_NC                                          = 'Processing failed because of bad input';
    const BAD_REQUEST_REQUIRED_FIELDS_FOR_NC                                        = 'Processing failed because input does not have all fields';
    const BAD_REQUEST_CLARIFICATIONS_PENDING_NC                                     = 'Processing failed because more clarifications required';
    const BAD_REQUEST_INVALID_DOCUMENT_FOR_NC                                       = 'Processing failed because of invalid document';
    const BAD_REQUEST_INVALID_MERCHANT_STATUS_NC                                    = 'Processing failed because merchant is not in Needs Clarification';
    const INVALID_STATUS_CHANGE_NC                                                  = 'Processing failed because no clarifications asked';

    // 1cc Gift Card Error Description
    const SAME_GIFT_CARD_APPLIED                                                    = 'Same gift card cannot be applied again';
    const GIFT_CARD_INVALID                                                         = 'Gift Card is either expired or invalid';
    const GIFT_CARD_EXTERNAL_REQUEST_FAILED                                         = 'Gift Card external request failed';
    const VALIDATE_GIFT_CARD_RESPONSE_JSON_ERROR                                    = 'Validate gift card response json error';
    const MERCHANT_DOMAIN_URL_NOT_PRESENT                                           = 'domain url not present';
    const GIFT_CARD_BALANCE_LESS_THAN_PREVIOUS_APPLIED                              = 'gift card amount less than applied';
    const GIFT_CARD_APPLICATION_NOT_ALLOWED                                         = 'Gift card application not allowed';
    const GIFT_CARD_ORDER_ALREADY_REFUNDED                                          = 'Gift card request cannot be processed as order is already refunded';

    const BAD_REQUEST_MERCHANT_NOT_ONBOARDED_FOR_TOKENISATION                       = 'The tokenisation request failed due to a configuration issue. Please reach out to the seller.';

    const BAD_REQUEST_OPTIMIZER_ONLY_MERCHANT_HAS_RAAS_DISABLED                     = 'Error : Payments have not been configured for this merchant.';

    const BAD_REQUEST_AUTHZ_ROLES_NOT_FOUND                                         = 'AuthZ Roles not found for the role id.';

    const BAD_REQUEST_TOKEN_NOT_APPLICABLE                                          = 'This saved card is no longer compliant with the RBI guidelines. Please use another card/payment method.';

    const BAD_REQUEST_TOKEN_CREATION_FAILED                                         = 'Token creation failed';

    const BAD_REQUEST_CARD_NOT_ELIGIBLE_FOR_TOKENISATION                            = 'The card is not eligible for tokenisation.';

    const BAD_REQUEST_CARD_DECLINED                                                 = 'The card is currently not eligible for tokenisation. The request was declined by card network.';

    const BAD_REQUEST_CARD_NOT_ALLOWED_BY_BANK                                      = 'The card is not allowed for tokenization due to some reasons at issuer bank.';

    const TOKEN_SERVICE_PROVIDER_ERROR                                              = 'The tokenisation request failed due to unexpected error with token service provider.';

    const BAD_REQUEST_CARD_NOT_ELIGIBLE                                             = 'The card is not eligible for tokenisation.';

    const BAD_REQUEST_CARD_NOT_ALLOWED                                              = 'The card is not allowed for tokenization due to some reasons at network end.';

    const BAD_REQUEST_CARD_INVALID                                                  = 'The card data provided are invalid. Please check & try again.';

    const BAD_REQUEST_INCORRECT_RESERVATION_OR_CANCELLATION_ID_FOR_REFUND           = 'Invalid request. The Reservation/Cancellation ID should be numeric entries';

    const BAD_REQUEST_DYNAMIC_QR_CODE_FIXED_AMOUNT_FAILURE                          = 'fixed_amount=true is required for Single use QR';

    const BAD_REQUEST_STATIC_QR_CODE_EXPIRY_FAILURE                                 = 'close_by for Multiple use QR code is not supported';

    const BAD_REQUEST_CLOSE_STATIC_QR_CODE_FAILURE                                  = 'Multiple use QR code cannot be closed';

    const BAD_REQUEST_ACCOUNT_ALREADY_DELETED                                       = 'Bank account is already deleted';

    const BAD_REQUEST_ACCOUNT_DOES_NOT_EXIST                                        = 'Bank account does not exist';

    const BAD_REQUEST_QR_CODE_ON_DEMAND_CLOSE_FOR_YES_BANK                          = 'Your current configuration does not support QR creation. Contact support for further assistance';

    const BAD_REQUEST_ACCOUNT_ID_IN_BODY                                            = 'Account id not allowed in body';

    const BAD_REQUEST_PAYOUT_APPROVAL_TOKEN_INVALID                                 = 'Token used is invalid to approve/reject this Payout';

    const BAD_REQUEST_PAYMENT_INSTRUMENT_NOT_ENABLED                                = 'Your payment could not be completed due to a temporary technical issue. To complete the payment, use another payment instrument.';
}
