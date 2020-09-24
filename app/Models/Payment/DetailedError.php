<?php


namespace RZP\Models\Payment;


class DetailedError
{
    /**
     * Source fields of detailed error
     */
    public static $sourceFieldMap = [
        "CX"                                                          =>         "customer",
        "GW"                                                          =>         "gateway",
        "MC"                                                          =>         "merchant",
        "IN"                                                          =>         "internal",
        "BK"                                                          =>         "bank",
        "BU"                                                          =>         "business",
        "IB"                                                          =>         "issuer_bank",
        "NW"                                                          =>         "network",
        "IS"                                                          =>         "issuer",
        "CP"                                                          =>         "customer_psp",
        "BB"                                                          =>         "beneficary_bank",
        "PR"                                                          =>         "provider",
        "NA"                                                          =>         "NA",
    ];


    /**
     * Step fields of detailed error
     */
    public static $stepFieldMap = [
       "EC"                                                          =>          "card_enrollment_check",
       "AN"                                                          =>          "payment_authentication",
       "PI"                                                          =>          "payment_initiation",
       "AZ"                                                          =>          "payment_authorization",
       "VF"                                                          =>          "verification",
       "CT"                                                          =>          "payment_capture",
       "EL"                                                          =>          "payment_eligibility_check",
       "DQ"                                                          =>          "payment_debit_request",
       "DP"                                                          =>          "payment_debit_response",
       "RQ"                                                          =>          "payment_request",
       "RP"                                                          =>          "payment_response",
       "CQ"                                                          =>          "payment_credit_request",
       "CP"                                                          =>          "payment_credit_response",
       "SQ"                                                          =>          "payment_status_request",
       "RB"                                                          =>          "payment_request_beneficiary_details",
       "NA"                                                          =>          "NA",
    ];
    /**
     * Reason fields of detailed error
     */

    public static $reasonFieldMap = [
        "NA"                                                            =>              "NA",
        "R001"                                   						=>				"payment_timed_out",
        "R002"                                   						=>				"gateway_technical_error",
        "R003"                                   						=>				"card_network_not_enabled",
        "R004"                                   						=>				"payment_cancelled",
        "R005"                                   						=>				"server_error",
        "R006"                                   						=>				"card_declined",
        "R007"                                   						=>				"transaction_limit_exceeded",
        "R008"                                   						=>				"invalid_currency",
        "R009"                                   						=>				"payment_failed",
        "R010"                                   						=>				"recurring_payment_not_enabled",
        "R011"                                   						=>				"input_validation_failed",
        "R012"                                   						=>				"payment_session_expired",
        "R013"                                   						=>				"live_mode_not_enabled",
        "R014"                                   						=>				"invalid_amount",
        "R015"                                   						=>				"card_not_enrolled",
        "R016"                                   						=>				"transaction_daily_limit_exceeded",
        "R017"                                   						=>				"insufficient_funds",
        "R018"                                   						=>				"international_transaction_blocked_on_card",
        "R019"                                   						=>				"debit_instrument_blocked",
        "R020"                                   						=>				"payment_risk_check_failed",
        "R021"                                   						=>				"debit_instrument_inactive",
        "R022"                                   						=>				"incorrect_card_details",
        "R023"                                   						=>				"otp_attempts_exceeded",
        "R024"                                   						=>				"pin_attempts_exceeded",
        "R025"                                   						=>				"incorrect_pin",
        "R026"                                   						=>				"incorrect_cvv",
        "R027"                                   						=>				"card_expired",
        "R028"                                   						=>				"authentication_failed",
        "R029"                                   						=>				"bank_technical_error",
        "R030"                                   						=>				"card_number_invalid",
        "R031"                                   						=>				"card_type_invalid",
        "R032"                                   						=>				"incorrect_cardholder_name",
        "R033"                                   						=>				"incorrect_card_expiry_date",
        "R034"                                   						=>				"transaction_daily_count_exceeded",
        "R035"                                   						=>				"incorrect_otp",
        "R036"                                   						=>				"capture_failed",
        "R037"                                   						=>				"user_not_eligible",
        "R038"                                   						=>				"credit_limit_not_approved",
        "R039"                                   						=>				"credit_limit_expired",
        "R040"                                   						=>				"credit_limit_inactive",
        "R041"                                   						=>				"credit_limit_exceeded",
        "R042"                                   						=>				"emi_plan_unavailable",
        "R043"                                   						=>				"emi_greater_than_max_amount",
        "R044"                                   						=>				"user_not_registered_for_netbanking",
        "R045"                                   						=>				"bank_account_invalid",
        "R046"                                   						=>				"bank_account_validation_failed",
        "R047"                                   						=>				"payment_pending",
        "R048"                                   						=>				"bank_not_enabled",
        "R049"                                   						=>				"amount_less_than_minimum_amount",
        "R050"                                   						=>				"payment_method_not_enabled",
        "R051"                                   						=>				"payment_declined",
        "R052"                                   						=>				"payment_collect_request_expired",
        "R053"                                   						=>				"invalid_vpa",
        "R054"                                   						=>				"invalid_device",
        "R055"                                   						=>				"upi_app_ technical_error",
        "R056"                                   						=>				"mobile_number_invalid",
        "R057"                                   						=>				"pin_not_set",
        "R058"                                   						=>				"upi_app_technical_error",
        "R059"                                   						=>				"vpa_resolution_failed",
        "R060"                                   						=>				"issuer_technical_error",
        "R061"                                   						=>				"invalid_user_details",
        "R062"                                   						=>				"invalid_email",
        "R063"                                   						=>				"otp_expired",
        "R064"                                   						=>				"invalid_mobile_number",
        "R065"                                   						=>				"amount_mismatch",
        "R066"                                   						=>				"extra_field_sent",
        "R067"                                   						=>				"decryption_failed",
        "R068"                                   						=>				"signing_key_expired",
        "R069"                                   						=>				"message_expired",
        "R070"                                   						=>				"payment_not_found",
        "R071"                                   						=>				"invalid_merchant_id",
        "R072"                                   						=>				"incorrect_request",
        "R073"                                                          =>              "payment_mandate_not_active",
        "R074"                                                          =>              "payment_pending_approval",
        "R075"                                                          =>              "gateway",
        ];
}
