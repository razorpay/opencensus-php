<?php

namespace RZP\Models\Payout;

use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;

class ErrorCodeMapping
{
    public static $failureReasonMapping = [
        'YB_NS_E400'                            => 'Payout failed. Contact support for help.',
        'YB_NS_E402'                            => 'Payout failed. Contact support for help.',
        'YB_NS_E405'                            => 'Payout failed. Contact support for help.',
        'YB_NS_E429'                            => 'Payout failed. Please reinitiate transfer after 30 min',
        'YB_NS_E406'                            => 'Issue with Beneficiary Account. Check Beneficiary Account before reinitiating transfer',
        'YB_NS_E502'                            => 'Payout failed. Please reinitiate transfer after 30 min',
        'YB_NS_E504'                            => 'Payout failed. Please reinitiate transfer after 30 min',
        'YB_NS_E1001'                           => 'Transaction amount exceeds IMPS maximum limit',
        'YB_NS_E1002'                           => 'Transfer currency is not supported. Contact support for help',
        'YB_NS_E1004'                           => 'Transfer amount is less than minimum RTGS limit',
        'YB_NS_E1005'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E1006'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E1028'                           => 'IMPS is not enabled on Beneficiary Account',
        'YB_NS_E2000'                           => 'Invalid Beneficiary details ',
        'YB_NS_E6000'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E6001'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E6002'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E6003'                           => 'Payout failed. Reinitiate transfer after 60 min',
        'YB_NS_E6005'                           => 'Invalid Beneficiary IFSC for GST payment',
        'YB_NS_E6006'                           => 'Beneficiary account no length is invalid for GST payment. Allowed length is 14',
        'YB_NS_E6007'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E6008'                           => 'Payout failed. Contact support for help.',
        'YB_FLEX_E18'                           => 'Payout failed. Reinitiate transfer after 60 min',
        'YB_FLEX_E307'                          => 'Issue at partner bank. Reinitiate transfer after 30 min',
        'YB_FLEX_E404'                          => 'Payout failed. Contact support for help.',
        'YB_FLEX_E449'                          => 'Invalid Beneficiary details',
        'YB_FLEX_E8036'                         => 'Payout failed. Reinitiate transfer after 60 min',
        'YB_FLEX_E8087'                         => 'Beneficiary Account number is invalid',
        'YB_FLEX_E9072'                         => 'Beneficiary bank branch could not be resolved. Retry after correcting beneficiary IFSC Code ',
        'YB_NPCI_E08'                           => 'NPCI or Beneficiary bank systems are offline. Reinitiate transfer after 30 min',
        'YB_NPCI_EM1'                           => 'Invalid Beneficiary MMID/Mobile Number',
        'YB_NPCI_EM2'                           => 'Transaction Amount exceeds IMPS Maximum Limit',
        'YB_NPCI_EM3'                           => 'Beneficiary account is blocked or frozen',
        'YB_NPCI_EM4'                           => 'Beneficiary account is not enabled for Foreign Inward Remittance',
        'YB_NPCI_EM5'                           => 'Beneficiary account is closed',
        'YB_NPCI_E307'                          => 'Failure/Rejection at beneficiary bank',
        'YB_NPCI_E308'                          => 'Failure/Rejection at beneficiary bank',
        'YB_NPCI_E449'                          => 'Rejected by beneficiary bank. Check beneficiary account details',
        'YB_ATOM_E307'                          => 'IMPS service issue at bank. Reinitiate transfer after 30 min',
        'YB_ATOM_E404'                          => 'Payout failed. Reinitiate transfer after 60 min',
        'YB_ATOM_E449'                          => 'Rejected by beneficiary bank. Check beneficiary account details',
        'YB_SFMS_E99'                           => 'Payout failed. Contact support for help.',
        'YB_SFMS_E70'                           => 'Payout failed. Contact support for help.',
        'YB_SFMS_E18'                           => 'Payout failed. Reinitiate transfer after 60 min',
        'YB_NS_E1029'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E2005'                           => 'Payout failed. Reinitiate transfer after 30 min',
        'YB_NS_E404'                            => 'Payout failed. Contact support for help.',
        'YB_SFMS_E62'                           => 'Beneficiary bank could not credit beneficiary account. Check beneficiary account details',
        'YB_NPCI_E68'                           => 'Beneficiary bank is offline. Reinitiate transfer after 30 min',
        'YB_SFMS_EREJ'                          => 'Payout failed. Contact support for help.',
        'YB_NS_E500'                            => 'Internal server error at bank. Reinitiate transfer after 30 min',
        'YB_NS_E403'                            => 'Payout failed. Contact support for help.',
        'YB_FLEX_E11'                           => 'Payout failed. Contact support for help.',
        'YB_FLEX_E2853'                         => 'Payout failed. Contact support for help.',
        'YB_NPCI_E450'                          => 'Rejected by beneficiary bank. Check beneficiary account details',
        'YB_FLEX_E3403'                         => 'Payout failed. Reinitiate transfer after 30 min',
        'YB_ATOM_E11'                           => 'Payout failed. Reinitiate transfer after 30 min',
        'YB_FLEX_E8086'                         => 'Payout failed. Contact support for help.',
        'YB_HTTP_STATUS_500'                    => 'Bank is facing issues. Reinitiate transfer after 30 min',
        'YB_HTTP_STATUS_404'                    => 'Payout failed. Contact support for help.',
        'YB_NPCI_E52'                           => 'Payout failed. Contact support for help.',
        'YB_NPCI_E13'                           => 'Payout failed. Contact support for help.',
        'YB_FLEX_E2435'                         => 'Beneficiary account is Dormant',
        'YB_FLEX_E6833'                         => 'Payout failed. Contact support for help.',
        'YB_NPCI_E91'                           => 'Payout failed. Contact support for help.',
        'YB_FLEX_E8035'                         => 'Mobile number is not valid',
        'YB_NPCI_E0'                            => 'Payout failed. Contact support for help.',
        'YB_NPCI_E80'                           => 'Payout failed. Contact support for help.',
        'YB_NS_404'                             => 'Payout failed. Contact support for help.',
        'YB_FLEX_E9074'                         => 'Narration is not valid. Contact support for help',
        'YB_NPCI_ECE'                           => 'Payout failed. Reinitiate transfer after 30 min',
        'YB_ATOM_EA19'                          => 'Payout failed. Reinitiate transfer after 30 min',
        'YB_FLEX_E8037'                         => 'Payout failed. Reinitiate transfer after 60 min',
        'YB_FLEX_E7585'                         => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NPCI_E01'                           => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NPCI_E96'                           => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NPCI_E20'                           => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NPCI_E12'                           => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NPCI_E92'                           => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NPCI_E22'                           => 'Internal Server Error at Partner Bank. Reinitiate transfer after 30 min',
        'YB_NS_E8000'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E410'                            => 'Payout failed. Contact support for help.',
        'YB_NS_E9001'                           => 'Payout failed. Contact support for help.',
        'YB_NS_E9002'                           => 'Failed while processing at the bank. Reinitiate transfer after 30 Minutes',
        'YB_ATOM_E4_50'                         => 'Bank rejected transaction due to incorrect format or beneficiary details.',
        'YB_FLEX_E11_017'                       => 'Bank rejected transaction due to incorrect format or beneficiary details.',
        'YB_NPCI_E02'                           => 'Connectivity issue with NPCI. Reinitiate transfer after 30 min',
        'YB_SFMS_99'                            => 'Payout failed. Contact support for help.',
        'YB_SFMS_E55'                           => 'Transaction is pending at beneficiary bank',
        'YB_SFMS_E59'                           => 'Beneficiary bank\'s systems are down. Please retry after some time.',
        'YB_NPCI_ENA'                           => 'Payout failed. Contact support for help.',
        'YB_NPCI_EMP'                           => 'Partner bank facing issue. Reinitiate the transaction after some time. ',
        'YB_NPCI_E30'                           => 'Payout failed. Contact support for help.',
        'VALIDATION_ERROR'                      => 'Payout failed. Contact support for help.',
        'FAILURE'                               => 'Payout failed. Reinitiate transfer after 30 min.',
        'INSUFFICIENT_FUND'                     => 'Payout failed. Reinitiate transfer after 30 min.',
        'INVALID_REQUEST'                       => 'Payout failed. Contact support for help.',
        'TXN_LIMIT_EXCEEDED'                    => 'Payout failed. Contact support for help.',
        'BAD_GATEWAY'                           => 'Payout failed. Contact support for help.',
        'TECHNICAL_ERROR'                       => 'Payout failed. Contact support for help.',
        'TXN_REJECTED'                          => 'Technical issue at partner bank. Please retry after 30 mins.',
        'MERCHANT_VALIDATION_ERROR'             => 'Invalid beneficiary details.',
        'EXCEEDED_AMOUNT_LIMIT'                 => 'Transaction Amount greater than the limit supported by the beneficiary bank.',
        'DUPLICATE_TXN_PENDING'                 => 'Payout failed. Contact support for help.',
        'TXN_TIMEOUT'                           => 'Payout failed. Please reinitiate transfer after 30 min.',
        'TXN_REJECTED_BENE_BANK'                => 'Technical issue at beneficiary bank. Please retry after 30 mins.',
        'BANK_CBS_OFFLINE_FAILURE'              => 'NPCI or Beneficiary bank systems are offline. Reinitiate transfer after 30 min',
        'DORMANT_ACCOUNT'                       => 'Beneficiary Account is Dormant. Please check with Beneficiary Bank.',
        'CLOSED_ACCOUNT'                        => 'Beneficiary Account is Closed. Please contact beneficiary bank.',
        'IMPS_NOT_ENABLED'                      => 'IMPS is not enabled on Beneficiary Account',
        'FUNDS_ON_HOLD'                         => 'Payout failed. Reinitiate transfer after 60 min.',
        'BENEFICIARY_MERCHANT'                  => 'Payout failed. Contact support for help.',
        'TXN_ON_HOLD'                           => 'Payout failed. Reinitiate transfer after 60 min.',
        'GATEWAY_DOWNTIME'                      => 'Payout failed. Contact support for help.',
        'BENE_BANK_OFFLINE'                     => 'Beneficiary bank is offline. Reinitiate transfer after 30 min.',
        'AUTHORIZATION_ISSUE_PENDING'           => 'Payout failed. Contact support for help.',
        'BANK_CBS_ISSUE_PENDING'                => 'Partner Bank is experiencing downtime. Reinitiate transfer after 30 min.',
        'NPCI_RESPONSE_ISSUE'                   => 'Payout failed. Contact support for help.',
        'BENE_BANK_RESPONSE_AWAITED'            => 'Beneficiary bank\'s systems are down. Please retry after some time.',
        'AUTHORIZATION_FAILED'                  => 'Payout failed. Contact support for help.',
        'AUTHORIZATION_FAILED_RETRIABLE'        => 'Payout failed. Contact support for help.',
        'FROZEN_ACCOUNT'                        => 'Beneficiary Account is Frozen. Please contact beneficiary bank.',
        'INVALID_AMOUNT'                        => 'Payout failed. Contact support for help.',
        'INVALID_VPA'                           => 'Invalid beneficiary VPA/UPI address',
        'COLLECT_REQUEST_REJECTED'              => 'Payout failed. Contact support for help.',
        'FRAUD_DECLINE'                         => 'Payout rejected by beneficiary bank. Please contact beneficiary bank.',
        'CARD_VALIDATION_FAILED'                => 'Payout failed. Contact support for help.',
        'TXN_NOT_ALLOWED'                       => 'Transaction not permitted to beneficiary account.',
        'TXN_NOT_ALLOWED_RETRIABLE'             => 'Transaction not permitted to beneficiary account.',
        'INVALID_BENEFICIARY_DETAILS'           => 'Invalid beneficiary details.',
        'INVALID_ACCOUNT_NUMBER'                => 'Invalid beneficiary account number',
        'TXN_TIMEOUT_PENDING'                   => 'Payout failed. Contact support for help.',
        'TECHNICAL_ERROR_PENDING'               => 'Payout failed. Contact support for help.',
        'VALIDATION_ERROR_PENDING'              => 'Payout failed. Contact support for help.',
        'AUTHENTICATION_FAILED'                 => 'Payout failed. Contact support for help.',
        'RETURNED'                              => 'Payout failed. Contact support for help.',
        'REQUEST_NOT_FOUND'                     => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'REQUEST_NOT_FOUND_RETRIABLE'           => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'PBANK_REQUEST_NOT_FOUND'               => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'REQUEST_NOT_FOUND_PENDING'             => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'REQ_NOT_FOUND_RECON_PENDING'           => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'PBANK_CBS_REJECTED'                    => 'Issue at partner bank. Reinitiate transfer after 30 min',
        'INVALID_IFSC'                          => 'IFSC Code is Not Valid. Please check and retry.',
        'MERCHANT_INVALID_TXN_DETAILS'          => 'Narration provided is not supported. Please check and retry.',
        'REASON_UNKNOWN'                        => 'Payout failed. Contact support for help.',
        'NRE_ACCOUNT'                           => 'Beneficiary Account is NRE. Please check and retry.',
        'BENEFICIARY_NAME_MISMATCH'             => 'Beneficiary Name Mismatch. Please check and retry.',
        'INVALID_BENEFICIARY_ACCOUNT'           => 'Invalid Beneficiary Account. Please check and retry.',
        'TECHNICAL_ERROR_FAILURE'               => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'PBANK_BENE_NOT_REGISTERED'             => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'TXN_TIMEOUT_FAILURE'                   => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'PBANK_GATEWAY_THROTTLED'               => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'BATCH_LIMIT_EXHAUSTED'                 => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'BBANK_OFFLINE'                         => 'NPCI or Beneficiary bank systems are offline. Reinitiate transfer after 30 min.',
        'Invalid_PSP'                           => 'Invalid Beneficiary PSP. Please check and retry.',
        'BENE_PSP_OFFLINE'                      => 'Beneficiary PSP is down. Please retry after 30 min.',
        'NPCI_TIMEOUT_FAILURE'                  => 'Timeout between NPCI and beneficiary bank. Please retry after 30 min.',
        'PBANK_VALIDATION_ERROR'                => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'PBANK_VALIDATION_ERROR_RETRIABLE'      => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'VERIFICATION_FAILED'                   => 'Payout failed. Contact support for help.',
        'BBANK_GATEWAY_THROTTLED_FAILURE'       => 'Beneficiary bank is offline. Reinitiate transfer after 30 min.',
        'BBANK_TECHNICAL_FAILURE'               => 'Payout failed at beneficiary bank due to technical issue. Please retry',
        'BENE_INCORRECT_IFSC'                   => 'Payout failed as the IFSC code is invalid. Please change the IFSC code and retry.',
        'FTS_ATTEMPT_INITIATE_FAILED'           => 'Payout failed due to technical failure. Please retry after 30 min.',
        'FTS_ATTEMPT_CREATE_FAILED'             => 'Payout failed due to technical failure. Please retry after 30 min.',
        'CUSTOMER_DOES_NOT_EXIST'               => 'Customer account does not exist with the wallet provider for the given phone number.',
        'MERCHANT_INSUFFICIENT_FUND'            => 'Payout failed due to insufficient funds in the bank account.',
        'FTS_MERCHANT_TXN_LIMIT_CROSSED'        => 'Payout failed due to extended NEFT window limit exhausted. Please try again in the next NEFT window.',
        'FTS_BANK_TXN_LIMIT_CROSSED'            => 'Payout failed as the partner bank is facing technical issues. Please retry',
        'MERCHANT_FROZEN_ACCOUNT'               => 'Payout failed as the debit account is under freeze by the partner bank.',
        'PBANK_VALIDATION_ERROR_PENDING'        => 'Temporary Issue at Partner bank. Reinitiate transfer after 30 min.',
        'TXN_REJECTED_BENE_BANK_RETRIABLE'      => 'Transaction not permitted to beneficiary account.',
        'PARTNER_BANK_DOWN'                     => 'Payout failed as the partner bank is facing technical issues. Please retry',
        'FTS_MANUAL_FAIL'                       => 'Payout failed due to bank window closed. Please try again in the next working window',
        'VAULT_SERVER_NOT_AVAILABLE'            => 'Payout failed. Contact support for help.',
        'VAULT_RESPONSE_EXPIRED'                => 'Payout failed. Contact support for help.',
        'VAULT_GENERIC_ERROR'                   => 'Payout failed. Contact support for help.',
        'VAULT_INVALID_FORMAT'                  => 'Payout failed. Contact support for help.',
        'VAULT_INVALID_VALUE'                   => 'Payout failed. Contact support for help.',
        'VAULT_TOKEN_NOT_FOUND'                 => 'Payout failed. Contact support for help.',
        'VAULT_TOKEN_INVALID_STATE'             => 'Payout failed. Contact support for help.',
        'VAULT_INVALID_HASH'                    => 'Payout failed. Contact support for help.',
        'VAULT_TOKEN_EXPIRED'                   => 'Payout failed. Contact support for help.',
        'VAULT_PANREF_NOT_FOUND'                => 'Payout failed. Contact support for help.',
        'CARD_NUMBER_UNAVAILABLE'               => 'Payout failed as the card number is not available. Please retry.',
        'FTS_OTP_NOT_FOUND'                     => 'Payout failed due to technical failure. Please retry after 30 min',
        'FTS_GATEWAY_REF_NUM_NOT_FOUND'         => 'Payout failed due to technical failure. Please retry after 30 min',
        'OTP_RETRIES_EXHAUSTED'                 => 'Payout failed due to multiple incorrect OTP(s). Please retry',
    ];

    public static $alternateFailureReasonMapping = [
        'INVALID_VPA'                           => 'UPI validation failed. If the UPI ID is valid, please retry after sometime.',
        'YB_SFMS_E59'                           => 'Beneficiary bank systems are down. Please retry after some time.',
        'BENE_BANK_RESPONSE_AWAITED'            => 'Beneficiary bank systems are down. Please retry after some time.',
        'YB_NPCI_EM1'                           => 'Invalid Beneficiary MMID or Mobile Number',
        'YB_NPCI_E307'                          => 'Failure or Rejection at beneficiary bank',
        'YB_NPCI_E308'                          => 'Failure or Rejection at beneficiary bank',
    ];

    public static $pendingReasonMapping = [
        'INVALID_OTP'                           => 'The OTP entered is incorrect. Request for a new OTP.',
        'EXPIRED_OTP'                           => 'The OTP has expired. Request for a new OTP.'
    ];

    const DEFAULT_FAILURE_REASON = 'Payout failed. Contact support for help.';

    public static function getErrorMessageFromBankResponseCode(Entity $payout, string $bankStatusCode = null)
    {
        $alternate = $payout->merchant->isFeatureEnabled(Feature\Constants::ALTERNATE_PAYOUT_FR);

        $errorMessage = null;

        if ($alternate === true)
        {
            $errorMessage = self::$alternateFailureReasonMapping[$bankStatusCode] ?? null;
        }

        if (is_null($errorMessage) === true)
        {
            $errorMessage = self::$failureReasonMapping[$bankStatusCode] ?? null;
        }

        if (is_null($errorMessage) === true)
        {
            app('trace')->error(TraceCode::PAYOUT_PUBLIC_ERROR_CODE_UNKNOWN_BANK_STATUS_CODE,
                [
                    'bank_status_code'  => $bankStatusCode,
                    'payout_id'         => $payout->getId(),
                ]);

            app('trace')->count(Metric::PAYOUT_PUBLIC_ERROR_CODE_UNMAPPED_BANK_STATUS_CODE, [
                'bank_status_code' => $bankStatusCode,
                'mode'             => app('request.ctx')->getMode() ?: 'none'
            ]);

            return self::DEFAULT_FAILURE_REASON;
        }

        return $errorMessage;
    }
}
