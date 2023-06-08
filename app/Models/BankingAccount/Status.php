<?php

namespace RZP\Models\BankingAccount;

use Carbon;
use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED               = 'created';            // Application Received
    const PICKED                = 'picked';             // Razorpay Processing
    const INITIATED             = 'initiated';          // Sent to Bank
    const VERIFICATION_CALL     = 'verification_call';  // Bank LMS - Verification Call
    const DOC_COLLECTION        = 'doc_collection';     // Bank LMS - Doc Collection
    const ACCOUNT_OPENING       = 'account_opening';    // Bank LMS - Account Opening
    const API_ONBOARDING        = 'api_onboarding';     // Bank LMS - API Onboarding
    const ACCOUNT_ACTIVATION    = 'account_activation'; // Bank LMS - Account Activation
    const PROCESSING            = 'processing';         // Bank Processing
    const PROCESSED             = 'processed';          // CA Opened
    const CANCELLED             = 'cancelled';          // Merchant Cancelled
    const ACTIVATED             = 'activated';          // CA Activated
    const UNSERVICEABLE         = 'unserviceable';      // Temp Unserviceable
    const REJECTED              = 'rejected';           // Bank Rejected
    const ARCHIVED              = 'archived';
    const TERMINATED            = 'terminated';       // Application Terminated


    // External Statuses as interpreted by Product

    const APPLICATION_RECEIVED           = 'ApplicationReceived';
    const RAZORPAY_PROCESSING            = 'RazorpayProcessing';
    const SENT_TO_BANK                   = 'SentToBank';
    const BANK_PROCESSING                = 'BankProcessing';
    const VERIFICATION_CALL_EXTERNAL     = 'VerificationCall';
    const DOC_COLLECTION_EXTERNAL        = 'DocCollection';
    const ACCOUNT_OPENING_EXTERNAL       = 'AccountOpening';
    const API_ONBOARDING_EXTERNAL        = 'ApiOnboarding';
    const ACCOUNT_ACTIVATION_EXTERNAL    = 'AccountActivation';
    const CA_OPENED                      = 'CAOpened';
    const MERCHANT_CANCELLED             = 'MerchantCancelled';
    const CA_ACTIVATED                   = 'CAActivated';
    const TEMP_UNSERVICEABLE             = 'TempUnserviceable';
    const BANK_REJECTED                  = 'BankRejected';
    const ARCHIVED_EXTERNAL              = 'Archived';
    const TERMINATED_EXTERNAL            = 'Terminated';

    // Substatuses
    const DOCS_WALK_THROUGH_PENDING      = 'docs_walkthrough_pending';
    const NEEDS_CLARIFICATION_FROM_SALES = 'needs_clarification_from_sales';
    const MERCHANT_NOT_AVAILABLE         = 'merchant_not_available';
    const MERCHANT_PREPARING_DOCS        = 'merchant_preparing_docs';
    const READY_TO_SEND_TO_BANK          = 'ready_to_send_to_bank';
    const BANK_TO_PICKUP_DOCS            = 'bank_to_pickup_docs';
    const NEEDS_CLARIFICATION_FROM_RZP   = 'needs_clarification_from_rzp';
    const BANK_PICKED_UP_DOCS            = 'bank_picked_up_docs';
    const DISCREPANCY_IN_DOCS            = 'discrepancy_in_docs';
    const BANK_OPENED_ACCOUNT            = 'bank_opened_account';
    const API_ONBOARDING_PENDING         = 'api_onboarding_pending';
    const API_ONBOARDING_INITIATED       = 'api_onboarding_initiated';
    const API_ONBOARDING_IN_PROGRESS     = 'api_onboarding_in_progress';
    const DOCKET_DELIVERY_PENDING        = 'docket_delivery_pending';
    const NONE                           = 'none';

    // Sub-status for OPS Telephonic verification
    const CONNECTIVITY__DID_NOT_PICK_UP_THE_PHONE = 'connectivity_|_did_not_pick_up_the_phone';
    const CONNECTIVITY__ASKED_TO_CALL_LATER = 'connectivity_|_asked_to_call_later';
    const CONNECTIVITY__DISCONNECTED_THE_CALL = 'connectivity_|_disconnected_the_call';
    const CONNECTIVITY__CONNECTIVITY_ISSUE = 'connectivity_|_connectivity_issue';
    const DOCUMENTATION__DOES_NOT_HAVE_ADDRESS_PROOF = 'documentation_|_does_not_have_address_proof';
    const DOCUMENTATION__DOES_NOT_HAVE_BUSINESS_PROOF = 'documentation_|_does_not_have_business_proof';
    const AVAILABILITY__OUT_OF_STATION_FOR_MORE_THAN_15_DAYS = 'availability_|_out_of_station_for_more_than_15_days';
    const AVAILABILITY__DIRECTORS_ARE_AT_UNSERVICEABLE_PINCODE = 'availability_|_directors_are_at_unserviceable_pincode';
    const AVAILABILITY__COVID19_RELATED_CONCERN = 'availability_|_covid-19_related_issue';
    const UNSERVICEABLE__PINCODE = 'unserviceable_|_pincode';
    const UNSERVICEABLE__BUSINESS_TYPE = 'unserviceable_|_business_type';
    const UNSERVICEABLE__BUSINESS_MODEL = 'unserviceable_|_business_model';
    const UNSERVICEABLE__UNREGISTERED_BUSINESS = 'unserviceable_|_unregistered_business';
    const NOT_INTERESTED_IN_CA__LOOKING_FOR_NEARBY_PHYSICAL_BRANCH = 'not_interested_in_CA_|_looking_for_nearby_physical_branch';
    const NOT_INTERESTED_IN_CA__ISSUE_WITH_RBL_BANK = 'not_interested_in_CA_|_issue_with_RBL_bank';
    const NOT_INTERESTED_IN_CA__ISSUE_WITH_TAT = 'not_interested_in_CA_|_issue_with_TAT';
    const NOT_INTERESTED_IN_CA__ISSUE_WITH_MAB_REQUIREMENT = 'not_interested_in_CA_|_issue_with_MAB_requirement';
    const NOT_INTERESTED_IN_CA__ISSUE_WITH_RX_PRICING = 'not_interested_in_CA_|_issue_with_RX_pricing';
    const NOT_INTERESTED_IN_CA__LOOKING_FOR_ONLY_PG_PRODUCTS = 'not_interested_in_CA_|_looking_for_only_PG_products';
    const NOT_INTERESTED_IN_CA__WANT_TO_LINK_EXISTING_CA = 'not_interested_in_CA_|_want_to_link_existing_CA';
    const NOT_INTERESTED_IN_CA__DID_NOT_HAVE_AN_INTENT = 'not_interested_in_CA_|_did_not_have_an_intent';
    const NOT_INTERESTED_IN_CA__OPENED_CA_WITH_OTHER_BANK = 'not_interested_in_CA_|_opened_CA_with_other_Bank';
    const NOT_INTERESTED_IN_CA__LOOKING_FOR_ZERO_BALANCE_CA = 'not_interested_in_CA_|_looking_for_zero_balance_CA';
    const NOT_INTERESTED_IN_CA__OTHER= 'not_interested_in_CA_|_other';
    const REQUIRES_SALES_INTERVENTION__DETAILS_ABOUT_CURRENT_ACCOUNT = 'requires_sales_intervention_|_details_about_current_account';
    const REQUIRES_SALES_INTERVENTION__UNCLEAR_ON_CA_PROCESS = 'requires_sales_intervention_|_unclear_on_CA_process';
    const REQUIRES_SALES_INTERVENTION__UNCLEAR_ON_RX_PRODUCT = 'requires_sales_intervention_|_unclear_on_RX_product';
    const REQUIRES_SALES_INTERVENTION__PRODUCT_DEMO = 'requires_sales_intervention_|_product_demo';
    const REQUIRES_SALES_INTERVENTION__RX_PRICING_DETAILS = 'requires_sales_intervention_|_RX_pricing_details';
    const REQUIRES_SALES_INTERVENTION__PG_DETAILS = 'requires_sales_intervention_|_PG_details';
    const REQUIRES_SALES_INTERVENTION__OTHER = 'requires_sales_intervention_|_other';
    const REGULATORY__BUSINESS_HAS_CC_OD_WITH_OTHER_BANK = 'regulatory_|_business_has_CC/OD_with_other_bank';
    const REGULATORY__FRAUD_OR_RISKY_MERCHANT = 'regulatory_|_fraud_or_risky_merchant';
    const FOLLOW_UP__WAITING_FOR_BUSINESS_DETAILS = 'follow_up_|_waiting_for_business_details';
    const FOLLOW_UP__WAITING_FOR_ADDRESS_DETAILS = 'follow_up_|_waiting_for_address_details';
    const FOLLOW_UP__WANT_CA_AT_LATER_DATE = 'follow_up_|_want_CA_at_later_date';
    const FOLLOW_UP__DIRECTORS_WILL_BE_AVAILABLE_AT_A_LATER_DATE = 'follow_up_|_directors_will_be_available_at_a_later_date';
    const FOLLOW_UP__NEED_TIME_TO_PREPARE_DOCS = 'follow_up_|_need_time_to_prepare_docs';
    const FOLLOW_UP__BUSINESS_GOING_THROUGH_ENTITY_CHANGES = 'follow_up_|_business_going_through_entity_changes';
    const FOLLOW_UP__REQUESTED_CALL_BACK_IN_REGIONAL_LANGUAGE = 'follow_up_|_requested_call_back_in_regional_language';
    const FOLLOW_UP__REQUESTED_CALL_BACK_IN_HINDI = 'follow_up_|_requested_call_back_in_hindi';
    const FOLLOW_UP__OTHER = 'follow_up_|_other';
    const OTHER = 'other'; // Need to confirm


    // New substatuses for Picked status
    const INITIATE_DOCKET  = 'initiate_docket';
    const DOCKET_INITIATED = 'docket_initiated';
    const DD_IN_PROGRESS   = 'dd_in_progress';
    const DWT_REQUIRED     = 'dwt_required';
    const DWT_COMPLETED    = 'dwt_completed';

    // External Substatuses as inputted by Ops/Sales teams via batch
    const DOCS_WALK_THROUGH_PENDING_EXTERNAL      = 'Docs Walkthrough Pending';
    const NEEDS_CLARIFICATION_FROM_SALES_EXTERNAL = 'Needs clarification from Sales';
    const MERCHANT_NOT_AVAILABLE_EXTERNAL         = 'Merchant is not Available';
    const MERCHANT_PREPARING_DOCS_EXTERNAL        = 'Merchant is preparing Docs';
    const READY_TO_SEND_TO_BANK_EXTRENAL          = 'Ready to send to Bank';
    const BANK_TO_PICKUP_DOCS_EXTERNAL            = 'Bank yet to pick up Docs';
    const BANK_PICKED_UP_DOCS_EXTERNAL            = 'Bank has picked up Docs';
    const NEEDS_CLARIFICATION_FROM_RZP_EXTERNAL   = 'Needs Clarification from RZP';
    const DISCREPANCY_IN_DOCS_EXTERNAL            = 'Discrepancy in Docs';
    const BANK_OPENED_ACCOUNT_EXTERNAL            = 'Bank Opened Account-Webhook Pending';
    const API_ONBOARDING_PENDING_EXTERNAL         = 'API onboarding is Pending on RZP';
    const API_ONBOARDING_INITIATED_EXTERNAL       = 'API onboarding has been initiated by RZP';
    const API_ONBOARDING_IN_PROGRESS_EXTERNAL     = 'API onboarding in Progress';
    const NONE_EXTERNAL                           = 'None';
    const UPI_ACTIVATED_EXTERNAL                  = 'UPI Activated';
    const OTHER_EXTERNAL                          = 'Others';
    const UNSERVICEABLE__PINCODE_EXTERNAL         = 'Unserviceable pincode';
    const NOT_SERVICEABLE_EXTERNAL                = 'Unserviceable pincode';
    const NEGATIVE_PROFILE_SVR_ISSUE_EXTERNAL     = 'Negative Profile/SVR issue';
    const UPI_CREDS_PENDING_EXTERNAL              = 'UPI Creds Pending';
    const CANCELLED_EXTERNAL                      = 'Cancelled';
    const CUSTOMER_NOT_RESPONDING_EXTERNAL            = 'Customer not responding - 3 attempts';
    const FOLLOW_UP_REQUESTED_BY_MERCHANT_EXTERNAL    = 'Follow up requested by Merchant';
    const VISIT_DUE_EXTERNAL                          = 'Visit Due';
    const PICKED_UP_DOCS_EXTERNAL                     = 'Picked Up Docs';
    const IR_IN_DISCREPANCY_EXTERNAL                  = 'IR in discrepancy';
    const IN_REVIEW_EXTERNAL                          = 'In Review';
    const CA_OPENED_SUB_STATUS_EXTERNAL               = 'CA Opened';
    const FOLLOW_UP_API_DOCS_UNAVAILABLE_EXTERNAL     = 'Follow up - API docs unavailable';



    // Sub-merchant BA status to be shown on partner dashboard
    const PAN_VERIFICATION_IN_PROGRESS      = 'PAN verification in progress';
    const PAN_VERIFICATION_FAILED           = 'PAN Verification Failed';
    const TELEPHONIC_VERIFICATION           = 'Telephonic verification';
    const APPLICATION_COMPLETION_PENDING    = 'Application completion pending';

    // Pending on Sales Sub-statuses as confirmed by Operations for NCFS Issue tracking
    const PENDING_ON_SALES_SUB_STRING                               = 'pending_on_sales_|_';
    const PENDING_ON_SALES_CONFIRMATION_TO_SEND_LEAD_PENDING        = self::PENDING_ON_SALES_SUB_STRING.'confirmation_to_send_lead_pending';
    const PENDING_ON_SALES_BUSINESS_DETAILS_PENDING                 = self::PENDING_ON_SALES_SUB_STRING.'business_details_pending';
    const PENDING_ON_SALES_DOC_WALKTHROUGH_CALL_NOT_SCHEDULED       = self::PENDING_ON_SALES_SUB_STRING.'doc_walkthrough_call_not_scheduled';
    const PENDING_ON_SALES_CONFIRMATION_ON_MULTIPLE_ACCOUNT_OPENING = self::PENDING_ON_SALES_SUB_STRING.'confirmation_on_multiple_account_opening';
    const PENDING_ON_SALES_MERCHANT_NOT_INTERESTED_SPOC_TO_CONFIRM  = self::PENDING_ON_SALES_SUB_STRING.'merchant_not_interested_(spoc_to_confirm)';
    const PENDING_ON_SALES_DOC_DELIVERY_ADDRESS_PENDING             = self::PENDING_ON_SALES_SUB_STRING.'doc-delivery_address_pending';
    const PENDING_ON_SALES_PINCODE_UNSERVICEABLE                    = self::PENDING_ON_SALES_SUB_STRING.'pincode_unserviceable';
    const PENDING_ON_SALES_AMB_AMOUNT_CONFIRMATION                  = self::PENDING_ON_SALES_SUB_STRING.'amb_amount_confirmation';
    const PENDING_ON_SALES_MERCHANT_PREPARING_KYC_DOCS              = self::PENDING_ON_SALES_SUB_STRING.'merchant_preparing_kyc_docs';
    const PENDING_ON_SALES_ISSUE_WITH_COMMERCIALS                   = self::PENDING_ON_SALES_SUB_STRING.'issue_with_commercials';
    const PENDING_ON_SALES_MERCHANT_WANTS_BANK_CHANGE               = self::PENDING_ON_SALES_SUB_STRING.'merchant_wants_bank_change';
    // Shortening the string value for the following substatuses as they exceed the allowed length in DB
    const PENDING_ON_SALES_DWT_NOT_COMPLETED_MX_NOT_RESPONDING_SPOC_TO_RESCHEDULE         = self::PENDING_ON_SALES_SUB_STRING.'dwt_not_completed_-_mx_not_responding'; // DWT Not Completed - MX Not Responding - SPOC to Reschedule
    const PENDING_ON_SALES_UNSUPPORTED_MISMATCH_OF_BIZ_TYPE_ON_ADMIN_DASHBOARD_AND_LMS    = self::PENDING_ON_SALES_SUB_STRING.'unsupported/_mismatch_of_biz_type'; // Unsupported/Mismatch of Biz Type on Admin Dashboard And LMS

    // Sub-statuses for Stage - Verification Call
    const IN_PROCESSING = 'in_processing';
    const CUSTOMER_CALL_ATTEMPTED = 'customer_call_attempted';
    const CUSTOMER_NOT_RESPONDING = 'customer_not_responding';
    const CUSTOMER_NOT_INTERESTED = 'customer_not_interested';
    const FOLLOW_UP_REQUESTED_BY_MERCHANT = 'follow_up_requested_by_merchant';
    const API_DOCKET_NOT_RECEIVED = 'api_docket_not_received';
    const KYC_ISSUE_WITH_CLIENT = 'kyc_issue_with_client';
    const ASSIGNED_TO_INSIGNIA = 'assigned_to_insignia';
    const ASSIGNED_TO_PCARM = 'assigned_to_pcarm';
    const ASSIGNED_TO_BRANCH = 'assigned_to_branch';

    // Sub-statuses for Doc Collection
    const VISIT_DUE = 'visit_due';
    const VISIT_RESCHEDULED = 'visit_rescheduled';
    const FOLLOW_UP_PARTIAL_AC_DOCS_AVAILABLE = 'follow_up_partial_ac_docs_available';
    const FOLLOW_UP_API_DOCS_UNAVAILABLE = 'follow_up_api_docs_unavailable';
    const PICKED_UP_DOCS = 'picked_up_docs';

    // Commonly used in Account Opening and API Onboarding
    const IN_REVIEW = 'in_review';
    const API_REGISTRATION_NOT_COMPLETE = 'api_registration_not_complete';
    const IR_RAISED = 'ir_raised';
    const IR_IN_DISCREPANCY = 'ir_in_discrepancy';
    const IR_IN_REWORK = 'ir_in_rework';
    const DOCS_VERIFIED = 'docs_verified';
    const CA_OPENED_SUB_STATUS = 'ca_opened';
    const API_IR_CLOSED = 'api_ir_closed';

    // Sub-statuses for Archived
    const IN_PROCESS = 'in_process';
    const CORP_ID_SENT = 'corp_id_sent';
    const CA_ACTIVATED_SUB_STATUS = 'ca_activated';
    const UPI_CREDS_PENDING = 'upi_creds_pending';
    const UPI_ACTIVATED = 'upi_activated';
    const CLIENT_NOT_RESPONDING = 'client_not_responding';
    const DIRECTOR_PARTNER_IS_UNAVAILABLE = 'director/partner_is_unavailable';
    const CLIENT_NOT_INTERESTED_ALREADY_HAS_ACCOUNT_WITH_DIFFERENT_BANK = 'client_not_interested_-_already_has_account_with_different_bank';
    const CLIENT_NOT_INTERESTED_DID_NOT_CLARIFY = 'client_not_interested_-_did_not_clarify';
    const CLIENT_NOT_INTERESTED_OPENED_ACCOUNT_IN_ANOTHER_BANK = 'client_not_interested_-_opened_account_in_another_bank';
    const CLIENT_NOT_INTERESTED_STALLING_APPOINTMENTS = 'client_not_interested_-_stalling_appointments';
    const CLIENT_NOT_INTERESTED_IP_CHEQUE_AMB_BANK_CHARGES = 'client_not_interested_-_ip_cheque/amb/bank_charges';
    const CLIENT_NOT_INTERESTED_WANTS_TO_LINK_EXISTING_CA = 'client_not_interested_-_wants_to_link_existing_ca';
    const CLIENT_NOT_INTERESTED_WANTS_TO_USE_ONLY_VA = 'client_not_interested_-_wants_to_use_only_va';
    const CLIENT_NOT_INTERESTED_RZP_ISSUE = 'client_not_interested_-_rzp_issue';
    const CLIENT_NOT_INTERESTED_RBL_BANK = 'client_not_interested_-_rbl_bank';
    const CLIENT_NOT_INTERESTED_DUE_TO_LONGER_TAT = 'client_not_interested_-_due_to_longer_tat';
    const ON_HOLD_BY_CLIENT = 'on_hold_by_client';
    const BUSINESS_IS_NOT_OPERATIONAL = 'business_is_not_operational';
    const NEGATIVE_PROFILE_SVR_ISSUE = 'negative_profile/svr_issue';
    const KYC_ADDRESS_PROOF_PENDING = 'kyc_-_address_proof_pending';
    const KYC_STAMP_PENDING = 'kyc_-_stamp_pending';
    const KYC_BUSINESS_DETAILS_PENDING = 'kyc_-_business_details_pending';
    const INCOMPLETE_KYC = 'incomplete_kyc';
    const NOT_SERVICEABLE = 'unserviceable_pincode';
    const UNSERVICEABLE_PIN_CODE_FOR_SIGNATORY = 'unserviceable_pin_code_for_signatory';
    const UNSUPPORTED_RZP_BUSINESS_TYPE_MODEL = 'unsupported_rzp_business_type/model';
    const ENTITY_CHANGE_IN_PROGRESS = 'entity_change_in_progress';
    const CC_OD_WITH_OTHER_BANK = 'cc/od_with_other_bank';
    const CLIENT_PROCEEDING_WITH_DIFFERENT_RZP_MID = 'client_proceeding_with_different_rzp_mid';
    const CA_OPENED_ORGANICALLY = 'ca_opened_organically';
    const RM_DELAYS_IN_ACCOUNT_OPENING = 'rm_delays_in_account_opening';
    const TEST_ACCOUNT = 'test_account';

    // All Substatuses - Used for mapping status, substatus with other data points
    const ALL_SUBSTATUSES = '*';

    // Account details can be saved only if the status
    // of banking account is in below array
    //
    public static $allowedStatusForDetails = [
        self::PROCESSED,
        self::ACCOUNT_OPENING,
        self::API_ONBOARDING,
        self::ACCOUNT_ACTIVATION,
    ];

    protected static $initialStatuses = [
        Status::CREATED,
        Status::UNSERVICEABLE,
    ];

    public static $activatedStatuses = [
        self::ACTIVATED,
    ];

    public static $terminalStatuses = [
        self::CANCELLED,
        self::ACTIVATED,
        self::UNSERVICEABLE,
        self::REJECTED,
    ];

    protected static $statuses = [
        // When application is started at rzp side
        self::CREATED,
        // When Razorpay starts processing the application
        self::PICKED,
        // When application is sent to the bank
        self::INITIATED,
        // When bank starts processing the application
        self::PROCESSING,
        // When bank is making verfication call, assigning branch and RM
        self::VERIFICATION_CALL,
        // When bank RM picks up the docs
        self::DOC_COLLECTION,
        // When account is geting opened
        self::ACCOUNT_OPENING,
        // When credentials mail is sent by RZP
        self::API_ONBOARDING,
        // When Account is getting activated
        self::ACCOUNT_ACTIVATION,
        // when user cancels his application to open CA.
        self::CANCELLED,
        // when Bank has processed, and opened the CA.
        // The webhook that we received from the bank on
        // CA opening sets this state.
        self::PROCESSED,
        // when the user's pincode does not belong
        // to the region of pincodes serviceable
        self::UNSERVICEABLE,
        // when the user's application to open CA
        //is rejected by RBL for some reason
        self::REJECTED,
        // merchant stops responding altogether, or loss
        // of interest after the process is initiated.
        self::ARCHIVED,
        // API banking has been tested. CA is activated
        // and ready to use.
        self::ACTIVATED,
        // When application needs to be deleted to make room
        // for another application. An application in this state
        // is not expected to be revived without tech intervention
        self::TERMINATED,
    ];

    /**
     * @var array
     * This contains a substatus map that needs to be blocked
     * on special requests
     */
    protected static $blockedSubStatusMap = [
        self::READY_TO_SEND_TO_BANK => [
            self::ARCHIVED,
        ],
    ];

    /**
     * @var array
     * This contains a status map that keeps mapping of a status
     * to next possible statuses. This is to ensure the status
     * change on Banking Account Entity happens in an order.
     *
     * Processed should be accessible from any previous state
     * so that we are be able to consume the webhook payload details
     * and update the status to CA opened, agnostic to the status on admin dashboard.
     */
    protected static $fromToStatusMap = [
        self::CREATED => [
            self::PICKED,
            self::CANCELLED,
            self::PROCESSED,
            self::API_ONBOARDING,
            // This is for cases in Neostone where users submit the details in the form
            // but don’t respond when called.
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::PICKED => [
            self::INITIATED,
            self::UNSERVICEABLE,
            self::VERIFICATION_CALL,
            self::CANCELLED,
            self::PROCESSED,
            self::API_ONBOARDING,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::INITIATED => [
            self::PROCESSING,
            self::PROCESSED,
            self::VERIFICATION_CALL,
            self::API_ONBOARDING,
            self::CANCELLED,
            self::REJECTED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::PROCESSING => [
            self::PROCESSED,
            self::VERIFICATION_CALL,
            self::DOC_COLLECTION,
            self::ACCOUNT_OPENING,
            self::API_ONBOARDING,
            self::ACCOUNT_ACTIVATION,
            self::ACTIVATED,
            self::CANCELLED,
            self::REJECTED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::VERIFICATION_CALL => [
            self::PROCESSED,
            self::DOC_COLLECTION,
            self::ACCOUNT_OPENING,
            self::API_ONBOARDING,
            self::ACCOUNT_ACTIVATION,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::DOC_COLLECTION => [
            self::PROCESSED,
            self::VERIFICATION_CALL,
            self::ACCOUNT_OPENING,
            self::API_ONBOARDING,
            self::ACCOUNT_ACTIVATION,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::ACCOUNT_OPENING => [
            self::PROCESSED,
            self::VERIFICATION_CALL,
            self::DOC_COLLECTION,
            self::API_ONBOARDING,
            self::ACCOUNT_ACTIVATION,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::API_ONBOARDING => [
            self::PROCESSED,
            self::VERIFICATION_CALL,
            self::DOC_COLLECTION,
            self::ACCOUNT_OPENING,
            self::ACCOUNT_ACTIVATION,
            self::ACTIVATED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::ACCOUNT_ACTIVATION => [
            self::PROCESSED,
            self::VERIFICATION_CALL,
            self::DOC_COLLECTION,
            self::ACCOUNT_OPENING,
            self::API_ONBOARDING,
            self::ACTIVATED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::PROCESSED => [
            self::ACTIVATED,
            // Sometimes leads drop off after CA is opened.
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::UNSERVICEABLE => [
            self::PICKED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::ACTIVATED => [
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::CANCELLED => [
            // Sometimes Sales team is able to revive leads who
            // had earlier cancelled their request. This is to
            // restart the process.
            self::PICKED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::REJECTED  => [
            // Temporarily allowing this transition because of
            // https://razorpay.slack.com/archives/CRA6TGU8H/p1603954629097600?thread_ts=1603779164.072600&cid=CRA6TGU8H
            self::PROCESSED,
            self::ARCHIVED,
            self::TERMINATED
        ],
        self::ARCHIVED  => [
            self::PICKED,
            self::INITIATED,
            self::PROCESSING,
            self::PROCESSED,
            self::ACCOUNT_OPENING,
            self::API_ONBOARDING,
            self::TERMINATED
        ]
    ];

    # TODO: Finalize after checking with Product
    protected static $subStatuses = [
        self::NEEDS_CLARIFICATION_FROM_SALES,
        self::DOCS_WALK_THROUGH_PENDING,
        self::DOCKET_DELIVERY_PENDING,
        self::MERCHANT_NOT_AVAILABLE,
        self::MERCHANT_PREPARING_DOCS,
        self::READY_TO_SEND_TO_BANK,
        self::BANK_TO_PICKUP_DOCS,
        self::NEEDS_CLARIFICATION_FROM_RZP,
        self::BANK_PICKED_UP_DOCS,
        self::DISCREPANCY_IN_DOCS,
        self::BANK_OPENED_ACCOUNT,
        self::API_ONBOARDING_INITIATED,
        self::API_ONBOARDING_PENDING,
        self::API_ONBOARDING_IN_PROGRESS,
        self::CONNECTIVITY__DID_NOT_PICK_UP_THE_PHONE,
        self::CONNECTIVITY__ASKED_TO_CALL_LATER,
        self::CONNECTIVITY__DISCONNECTED_THE_CALL,
        self::AVAILABILITY__OUT_OF_STATION_FOR_MORE_THAN_15_DAYS,
        self::AVAILABILITY__DIRECTORS_ARE_AT_UNSERVICEABLE_PINCODE,
        self::AVAILABILITY__COVID19_RELATED_CONCERN,
        self::UNSERVICEABLE__PINCODE,
        self::UNSERVICEABLE__BUSINESS_TYPE,
        self::NOT_INTERESTED_IN_CA__DID_NOT_HAVE_AN_INTENT,
        self::NOT_INTERESTED_IN_CA__OPENED_CA_WITH_OTHER_BANK,
        self::NOT_INTERESTED_IN_CA__LOOKING_FOR_ZERO_BALANCE_CA,
        self::NOT_INTERESTED_IN_CA__OTHER,
        self::REQUIRES_SALES_INTERVENTION__PRODUCT_DEMO,
        self::REQUIRES_SALES_INTERVENTION__RX_PRICING_DETAILS,
        self::REQUIRES_SALES_INTERVENTION__PG_DETAILS,
        self::REQUIRES_SALES_INTERVENTION__OTHER,
        self::REGULATORY__BUSINESS_HAS_CC_OD_WITH_OTHER_BANK,
        self::REGULATORY__FRAUD_OR_RISKY_MERCHANT,
        self::CONNECTIVITY__CONNECTIVITY_ISSUE,
        self::DOCUMENTATION__DOES_NOT_HAVE_ADDRESS_PROOF,
        self::DOCUMENTATION__DOES_NOT_HAVE_BUSINESS_PROOF,
        self::UNSERVICEABLE__BUSINESS_MODEL,
        self::UNSERVICEABLE__UNREGISTERED_BUSINESS,
        self::NOT_INTERESTED_IN_CA__LOOKING_FOR_NEARBY_PHYSICAL_BRANCH,
        self::NOT_INTERESTED_IN_CA__ISSUE_WITH_RBL_BANK,
        self::NOT_INTERESTED_IN_CA__ISSUE_WITH_TAT,
        self::NOT_INTERESTED_IN_CA__ISSUE_WITH_MAB_REQUIREMENT,
        self::NOT_INTERESTED_IN_CA__ISSUE_WITH_RX_PRICING,
        self::NOT_INTERESTED_IN_CA__LOOKING_FOR_ONLY_PG_PRODUCTS,
        self::NOT_INTERESTED_IN_CA__WANT_TO_LINK_EXISTING_CA,
        self::REQUIRES_SALES_INTERVENTION__DETAILS_ABOUT_CURRENT_ACCOUNT,
        self::REQUIRES_SALES_INTERVENTION__UNCLEAR_ON_CA_PROCESS,
        self::REQUIRES_SALES_INTERVENTION__UNCLEAR_ON_RX_PRODUCT,
        self::FOLLOW_UP__WAITING_FOR_BUSINESS_DETAILS,
        self::FOLLOW_UP__WAITING_FOR_ADDRESS_DETAILS,
        self::FOLLOW_UP__WANT_CA_AT_LATER_DATE,
        self::FOLLOW_UP__DIRECTORS_WILL_BE_AVAILABLE_AT_A_LATER_DATE,
        self::FOLLOW_UP__NEED_TIME_TO_PREPARE_DOCS,
        self::FOLLOW_UP__BUSINESS_GOING_THROUGH_ENTITY_CHANGES,
        self::FOLLOW_UP__REQUESTED_CALL_BACK_IN_REGIONAL_LANGUAGE,
        self::FOLLOW_UP__REQUESTED_CALL_BACK_IN_HINDI,
        self::FOLLOW_UP__OTHER,
        self::PENDING_ON_SALES_CONFIRMATION_TO_SEND_LEAD_PENDING,
        self::PENDING_ON_SALES_BUSINESS_DETAILS_PENDING,
        self::PENDING_ON_SALES_DOC_WALKTHROUGH_CALL_NOT_SCHEDULED,
        self::PENDING_ON_SALES_CONFIRMATION_ON_MULTIPLE_ACCOUNT_OPENING,
        self::PENDING_ON_SALES_MERCHANT_NOT_INTERESTED_SPOC_TO_CONFIRM,
        self::PENDING_ON_SALES_DOC_DELIVERY_ADDRESS_PENDING,
        self::PENDING_ON_SALES_PINCODE_UNSERVICEABLE,
        self::PENDING_ON_SALES_AMB_AMOUNT_CONFIRMATION,
        self::PENDING_ON_SALES_DWT_NOT_COMPLETED_MX_NOT_RESPONDING_SPOC_TO_RESCHEDULE,
        self::PENDING_ON_SALES_UNSUPPORTED_MISMATCH_OF_BIZ_TYPE_ON_ADMIN_DASHBOARD_AND_LMS,
        self::PENDING_ON_SALES_MERCHANT_PREPARING_KYC_DOCS,
        self::PENDING_ON_SALES_ISSUE_WITH_COMMERCIALS,
        self::PENDING_ON_SALES_MERCHANT_WANTS_BANK_CHANGE,

        self::IN_PROCESSING,
        self::CUSTOMER_CALL_ATTEMPTED,
        self::CUSTOMER_NOT_RESPONDING,
        self::CUSTOMER_NOT_INTERESTED,
        self::FOLLOW_UP_REQUESTED_BY_MERCHANT,
        self::API_DOCKET_NOT_RECEIVED,
        self::KYC_ISSUE_WITH_CLIENT,
        self::ASSIGNED_TO_INSIGNIA,
        self::ASSIGNED_TO_PCARM,
        self::ASSIGNED_TO_BRANCH,
        self::VISIT_DUE,
        self::VISIT_RESCHEDULED,
        self::FOLLOW_UP_PARTIAL_AC_DOCS_AVAILABLE,
        self::FOLLOW_UP_API_DOCS_UNAVAILABLE,
        self::PICKED_UP_DOCS,
        self::IN_REVIEW,
        self::API_REGISTRATION_NOT_COMPLETE,
        self::IR_RAISED,
        self::IR_IN_DISCREPANCY,
        self::IR_IN_REWORK,
        self::DOCS_VERIFIED,
        self::CA_OPENED_SUB_STATUS,
        self::API_IR_CLOSED,
        self::IN_PROCESS,
        self::CORP_ID_SENT,
        self::CA_ACTIVATED_SUB_STATUS,
        self::UPI_CREDS_PENDING,
        self::UPI_ACTIVATED,
        self::CLIENT_NOT_RESPONDING,
        self::DIRECTOR_PARTNER_IS_UNAVAILABLE,
        self::CLIENT_NOT_INTERESTED_ALREADY_HAS_ACCOUNT_WITH_DIFFERENT_BANK,
        self::CLIENT_NOT_INTERESTED_DID_NOT_CLARIFY,
        self::CLIENT_NOT_INTERESTED_OPENED_ACCOUNT_IN_ANOTHER_BANK,
        self::CLIENT_NOT_INTERESTED_STALLING_APPOINTMENTS,
        self::CLIENT_NOT_INTERESTED_IP_CHEQUE_AMB_BANK_CHARGES,
        self::CLIENT_NOT_INTERESTED_WANTS_TO_LINK_EXISTING_CA,
        self::CLIENT_NOT_INTERESTED_WANTS_TO_USE_ONLY_VA,
        self::CLIENT_NOT_INTERESTED_RZP_ISSUE,
        self::CLIENT_NOT_INTERESTED_DUE_TO_LONGER_TAT,
        self::CANCELLED, // User requested to cancel archived/cancelled
        self::ON_HOLD_BY_CLIENT,
        self::BUSINESS_IS_NOT_OPERATIONAL,
        self::NEGATIVE_PROFILE_SVR_ISSUE,
        self::INCOMPLETE_KYC,
        self::NOT_SERVICEABLE,
        self::UNSUPPORTED_RZP_BUSINESS_TYPE_MODEL,
        self::ENTITY_CHANGE_IN_PROGRESS,
        self::CC_OD_WITH_OTHER_BANK,
        self::CLIENT_PROCEEDING_WITH_DIFFERENT_RZP_MID,
        self::CA_OPENED_ORGANICALLY,
        self::RM_DELAYS_IN_ACCOUNT_OPENING,

        self::OTHER,
        self::NONE,

        self::INITIATE_DOCKET,
        self::DOCKET_INITIATED,
        self::DD_IN_PROGRESS,
        self::DWT_REQUIRED,
        self::DWT_COMPLETED,

        self::CLIENT_NOT_INTERESTED_RBL_BANK,
        self::KYC_ADDRESS_PROOF_PENDING,
        self::KYC_STAMP_PENDING,
        self::KYC_BUSINESS_DETAILS_PENDING,
        self::UNSERVICEABLE_PIN_CODE_FOR_SIGNATORY,
        self::TEST_ACCOUNT
    ];

    protected static $defaultSubStatus = [
        self::PROCESSED => self::API_ONBOARDING_PENDING,
        self::VERIFICATION_CALL => self::IN_PROCESSING,
        self::DOC_COLLECTION => self::VISIT_DUE,
        self::ACCOUNT_OPENING => self::IN_REVIEW,
        self::API_ONBOARDING => self::IN_REVIEW,
        self::ACCOUNT_ACTIVATION => self::IN_PROCESS,
        self::ACTIVATED => self::UPI_CREDS_PENDING,
        self::ARCHIVED => self::IN_PROCESS,
    ];

    /**
     * @var array
     * This contains the allowed set of status<->substatus mappings
     */
    public static $statusToSubStatusMap = [
        self::CREATED => [
        ],
        self::PICKED => [
            self::NONE,
            self::DOCKET_DELIVERY_PENDING,
            self::MERCHANT_NOT_AVAILABLE,
            self::MERCHANT_PREPARING_DOCS,
            self::READY_TO_SEND_TO_BANK,
            self::DOCS_WALK_THROUGH_PENDING,
            self::NEEDS_CLARIFICATION_FROM_SALES,
            self::CONNECTIVITY__DID_NOT_PICK_UP_THE_PHONE,
            self::CONNECTIVITY__ASKED_TO_CALL_LATER,
            self::CONNECTIVITY__DISCONNECTED_THE_CALL,
            self::AVAILABILITY__OUT_OF_STATION_FOR_MORE_THAN_15_DAYS,
            self::AVAILABILITY__DIRECTORS_ARE_AT_UNSERVICEABLE_PINCODE,
            self::AVAILABILITY__COVID19_RELATED_CONCERN,
            self::UNSERVICEABLE__PINCODE,
            self::UNSERVICEABLE__BUSINESS_TYPE,
            self::NOT_INTERESTED_IN_CA__DID_NOT_HAVE_AN_INTENT,
            self::NOT_INTERESTED_IN_CA__OPENED_CA_WITH_OTHER_BANK,
            self::NOT_INTERESTED_IN_CA__LOOKING_FOR_ZERO_BALANCE_CA,
            self::NOT_INTERESTED_IN_CA__OTHER,
            self::REQUIRES_SALES_INTERVENTION__PRODUCT_DEMO,
            self::REQUIRES_SALES_INTERVENTION__RX_PRICING_DETAILS,
            self::REQUIRES_SALES_INTERVENTION__PG_DETAILS,
            self::REQUIRES_SALES_INTERVENTION__OTHER,
            self::REGULATORY__BUSINESS_HAS_CC_OD_WITH_OTHER_BANK,
            self::REGULATORY__FRAUD_OR_RISKY_MERCHANT,
            self::CONNECTIVITY__CONNECTIVITY_ISSUE,
            self::DOCUMENTATION__DOES_NOT_HAVE_ADDRESS_PROOF,
            self::DOCUMENTATION__DOES_NOT_HAVE_BUSINESS_PROOF,
            self::UNSERVICEABLE__BUSINESS_MODEL,
            self::UNSERVICEABLE__UNREGISTERED_BUSINESS,
            self::NOT_INTERESTED_IN_CA__LOOKING_FOR_NEARBY_PHYSICAL_BRANCH,
            self::NOT_INTERESTED_IN_CA__ISSUE_WITH_RBL_BANK,
            self::NOT_INTERESTED_IN_CA__ISSUE_WITH_TAT,
            self::NOT_INTERESTED_IN_CA__ISSUE_WITH_MAB_REQUIREMENT,
            self::NOT_INTERESTED_IN_CA__ISSUE_WITH_RX_PRICING,
            self::NOT_INTERESTED_IN_CA__LOOKING_FOR_ONLY_PG_PRODUCTS,
            self::NOT_INTERESTED_IN_CA__WANT_TO_LINK_EXISTING_CA,
            self::REQUIRES_SALES_INTERVENTION__DETAILS_ABOUT_CURRENT_ACCOUNT,
            self::REQUIRES_SALES_INTERVENTION__UNCLEAR_ON_CA_PROCESS,
            self::REQUIRES_SALES_INTERVENTION__UNCLEAR_ON_RX_PRODUCT,
            self::FOLLOW_UP__WAITING_FOR_BUSINESS_DETAILS,
            self::FOLLOW_UP__WAITING_FOR_ADDRESS_DETAILS,
            self::FOLLOW_UP__WANT_CA_AT_LATER_DATE,
            self::FOLLOW_UP__DIRECTORS_WILL_BE_AVAILABLE_AT_A_LATER_DATE,
            self::FOLLOW_UP__NEED_TIME_TO_PREPARE_DOCS,
            self::FOLLOW_UP__BUSINESS_GOING_THROUGH_ENTITY_CHANGES,
            self::FOLLOW_UP__REQUESTED_CALL_BACK_IN_REGIONAL_LANGUAGE,
            self::FOLLOW_UP__REQUESTED_CALL_BACK_IN_HINDI,
            self::FOLLOW_UP__OTHER,
            self::OTHER,
            self::PENDING_ON_SALES_CONFIRMATION_TO_SEND_LEAD_PENDING,
            self::PENDING_ON_SALES_BUSINESS_DETAILS_PENDING,
            self::PENDING_ON_SALES_DOC_WALKTHROUGH_CALL_NOT_SCHEDULED,
            self::PENDING_ON_SALES_CONFIRMATION_ON_MULTIPLE_ACCOUNT_OPENING,
            self::PENDING_ON_SALES_MERCHANT_NOT_INTERESTED_SPOC_TO_CONFIRM,
            self::PENDING_ON_SALES_DOC_DELIVERY_ADDRESS_PENDING,
            self::PENDING_ON_SALES_PINCODE_UNSERVICEABLE,
            self::PENDING_ON_SALES_AMB_AMOUNT_CONFIRMATION,
            self::PENDING_ON_SALES_DWT_NOT_COMPLETED_MX_NOT_RESPONDING_SPOC_TO_RESCHEDULE,
            self::PENDING_ON_SALES_UNSUPPORTED_MISMATCH_OF_BIZ_TYPE_ON_ADMIN_DASHBOARD_AND_LMS,
            self::PENDING_ON_SALES_MERCHANT_PREPARING_KYC_DOCS,
            self::PENDING_ON_SALES_ISSUE_WITH_COMMERCIALS,
            self::PENDING_ON_SALES_MERCHANT_WANTS_BANK_CHANGE,
            self::INITIATE_DOCKET,
            self::DOCKET_INITIATED,
            self::DD_IN_PROGRESS,
            self::DWT_REQUIRED,
            self::DWT_COMPLETED
        ],
        self::INITIATED => [
            self::NONE,
            self::MERCHANT_NOT_AVAILABLE,
            self::MERCHANT_PREPARING_DOCS,
            self::BANK_TO_PICKUP_DOCS,
            self::BANK_PICKED_UP_DOCS,
            self::NEEDS_CLARIFICATION_FROM_RZP,
        ],
        self::PROCESSING => [
            self::DISCREPANCY_IN_DOCS,
            self::BANK_OPENED_ACCOUNT,
        ],
        self::VERIFICATION_CALL => [
            self::IN_PROCESSING,
            self::CUSTOMER_CALL_ATTEMPTED,
            self::CUSTOMER_NOT_RESPONDING,
            self::CUSTOMER_NOT_INTERESTED,
            self::NEEDS_CLARIFICATION_FROM_RZP,
            self::FOLLOW_UP_REQUESTED_BY_MERCHANT,
            self::API_DOCKET_NOT_RECEIVED,
            self::KYC_ISSUE_WITH_CLIENT,
            self::ASSIGNED_TO_INSIGNIA,
            self::ASSIGNED_TO_PCARM,
            self::ASSIGNED_TO_BRANCH,
        ],
        self::DOC_COLLECTION => [
            self::VISIT_DUE,
            self::CUSTOMER_NOT_RESPONDING,
            self::VISIT_RESCHEDULED,
            self::FOLLOW_UP_REQUESTED_BY_MERCHANT,
            self::FOLLOW_UP_PARTIAL_AC_DOCS_AVAILABLE,
            self::FOLLOW_UP_API_DOCS_UNAVAILABLE,
            self::PICKED_UP_DOCS,
        ],
        self::ACCOUNT_OPENING => [
            self::IN_REVIEW,
            self::IR_RAISED,
            self::IR_IN_DISCREPANCY,
            self::IR_IN_REWORK,
            self::DOCS_VERIFIED,
            self::CA_OPENED_SUB_STATUS,
        ],
        self::API_ONBOARDING => [
            self::IN_REVIEW,
            self::API_REGISTRATION_NOT_COMPLETE,
            self::IR_RAISED,
            self::IR_IN_DISCREPANCY,
            self::IR_IN_REWORK,
            self::DOCS_VERIFIED,
            self::API_IR_CLOSED,
        ],
        self::ACCOUNT_ACTIVATION => [
            self::IN_PROCESS,
            self::CORP_ID_SENT,
            self::CA_ACTIVATED_SUB_STATUS,
        ],
        self::PROCESSED => [
            // Pending on RZP
            self::API_ONBOARDING_PENDING,
            // Initiated by RZP
            self::API_ONBOARDING_INITIATED,
            // In progress on Bank
            self::API_ONBOARDING_IN_PROGRESS,
            // Sometimes API onboarding related docs are
            // processed by Bank after opening account.
            self::MERCHANT_NOT_AVAILABLE,
            self::MERCHANT_PREPARING_DOCS,
            self::DISCREPANCY_IN_DOCS
        ],
        self::UNSERVICEABLE => [
        ],

        self::ACTIVATED => [
            self::UPI_CREDS_PENDING,
            self::UPI_ACTIVATED,
        ],
        self::CANCELLED => [
        ],
        self::REJECTED  => [
        ],
        self::ARCHIVED  => [
            self::IN_PROCESS,
            self::CLIENT_NOT_RESPONDING,
            self::DIRECTOR_PARTNER_IS_UNAVAILABLE,
            self::CLIENT_NOT_INTERESTED_ALREADY_HAS_ACCOUNT_WITH_DIFFERENT_BANK,
            self::CLIENT_NOT_INTERESTED_DID_NOT_CLARIFY,
            self::CLIENT_NOT_INTERESTED_OPENED_ACCOUNT_IN_ANOTHER_BANK,
            self::CLIENT_NOT_INTERESTED_STALLING_APPOINTMENTS,
            self::CLIENT_NOT_INTERESTED_IP_CHEQUE_AMB_BANK_CHARGES,
            self::CLIENT_NOT_INTERESTED_WANTS_TO_LINK_EXISTING_CA,
            self::CLIENT_NOT_INTERESTED_WANTS_TO_USE_ONLY_VA,
            self::CLIENT_NOT_INTERESTED_RZP_ISSUE,
            self::CLIENT_NOT_INTERESTED_DUE_TO_LONGER_TAT,
            self::CLIENT_NOT_INTERESTED_RBL_BANK,
            self::CANCELLED,
            self::ON_HOLD_BY_CLIENT,
            self::BUSINESS_IS_NOT_OPERATIONAL,
            self::NEGATIVE_PROFILE_SVR_ISSUE,
            self::KYC_ADDRESS_PROOF_PENDING,
            self::KYC_STAMP_PENDING,
            self::KYC_BUSINESS_DETAILS_PENDING,
            self::INCOMPLETE_KYC,
            self::NOT_SERVICEABLE,
            self::UNSERVICEABLE_PIN_CODE_FOR_SIGNATORY,
            self::UNSUPPORTED_RZP_BUSINESS_TYPE_MODEL,
            self::ENTITY_CHANGE_IN_PROGRESS,
            self::CC_OD_WITH_OTHER_BANK,
            self::CLIENT_PROCEEDING_WITH_DIFFERENT_RZP_MID,
            self::CA_OPENED_ORGANICALLY,
            self::RM_DELAYS_IN_ACCOUNT_OPENING,
            self::TEST_ACCOUNT,
            self::OTHER,
        ],
        self::TERMINATED => []
    ];


    /**
     * @var array
     * This contains the allowed set of status substatus <-> assignee team mappings
     */
    public static $statusAndSubStatusToAssigneeMap = [
        self::INITIATED => [
            self::NONE => Activation\Detail\Entity::BANK,
            null => Activation\Detail\Entity::BANK,
        ],
        self::VERIFICATION_CALL => [
            self::IN_PROCESSING => Activation\Detail\Entity::BANK,
            self::CUSTOMER_CALL_ATTEMPTED => Activation\Detail\Entity::BANK,
            self::CUSTOMER_NOT_RESPONDING =>  Entity::OPS_MX_POC,
            self::CUSTOMER_NOT_INTERESTED => Activation\Detail\Entity::SALES,
            self::NEEDS_CLARIFICATION_FROM_RZP => Activation\Detail\Entity::SALES,
            self::FOLLOW_UP_REQUESTED_BY_MERCHANT => Activation\Detail\Entity::BANK,
            self::API_DOCKET_NOT_RECEIVED => Activation\Detail\Entity::OPS,
            self::KYC_ISSUE_WITH_CLIENT => Entity::OPS_MX_POC,
            self::ASSIGNED_TO_INSIGNIA => Activation\Detail\Entity::BANK,
            self::ASSIGNED_TO_PCARM => Activation\Detail\Entity::BANK,
            self::ASSIGNED_TO_BRANCH => Activation\Detail\Entity::BANK,
        ],
        self::DOC_COLLECTION => [
            self::VISIT_DUE => Activation\Detail\Entity::BANK,
            self::CUSTOMER_NOT_RESPONDING => Entity::OPS_MX_POC,
            self::VISIT_RESCHEDULED => Activation\Detail\Entity::BANK,
            self::FOLLOW_UP_REQUESTED_BY_MERCHANT => Activation\Detail\Entity::BANK,
            self::FOLLOW_UP_PARTIAL_AC_DOCS_AVAILABLE => Activation\Detail\Entity::BANK,
            self::FOLLOW_UP_API_DOCS_UNAVAILABLE => Activation\Detail\Entity::BANK,
            self::PICKED_UP_DOCS => Activation\Detail\Entity::BANK,
        ],
        self::ACCOUNT_OPENING => [
            self::IN_REVIEW => Activation\Detail\Entity::BANK,
            self::IR_RAISED => Activation\Detail\Entity::BANK,
            self::IR_IN_DISCREPANCY => Activation\Detail\Entity::BANK,
            self::IR_IN_REWORK => Activation\Detail\Entity::BANK,
            self::DOCS_VERIFIED => Activation\Detail\Entity::BANK,
            self::CA_OPENED_SUB_STATUS => Activation\Detail\Entity::BANK,
        ],
        self::API_ONBOARDING => [
            self::IN_REVIEW => Activation\Detail\Entity::BANK,
            self::API_REGISTRATION_NOT_COMPLETE => Activation\Detail\Entity::OPS,
            self::IR_RAISED => Activation\Detail\Entity::BANK,
            self::IR_IN_DISCREPANCY => Activation\Detail\Entity::BANK,
            self::IR_IN_REWORK => Activation\Detail\Entity::BANK,
            self::DOCS_VERIFIED => Activation\Detail\Entity::BANK,
            self::API_IR_CLOSED => Activation\Detail\Entity::BANK,
        ],
        self::ACCOUNT_ACTIVATION => [
            self::IN_PROCESS => Activation\Detail\Entity::BANK,
            self::CORP_ID_SENT => Activation\Detail\Entity::OPS,
            self::CA_ACTIVATED_SUB_STATUS => Activation\Detail\Entity::OPS,
        ],
        self::ACTIVATED => [
            self::UPI_CREDS_PENDING => Activation\Detail\Entity::BANK,
            self::UPI_ACTIVATED => null,
        ],
        self::ARCHIVED => [
            self::IN_PROCESS => Entity::OPS_MX_POC,
            self::CLIENT_NOT_RESPONDING => null,
            self::DIRECTOR_PARTNER_IS_UNAVAILABLE => null,
            self::CLIENT_NOT_INTERESTED_ALREADY_HAS_ACCOUNT_WITH_DIFFERENT_BANK => null,
            self::CLIENT_NOT_INTERESTED_DID_NOT_CLARIFY => null,
            self::CLIENT_NOT_INTERESTED_OPENED_ACCOUNT_IN_ANOTHER_BANK => null,
            self::CLIENT_NOT_INTERESTED_STALLING_APPOINTMENTS => null,
            self::CLIENT_NOT_INTERESTED_IP_CHEQUE_AMB_BANK_CHARGES => null,
            self::CLIENT_NOT_INTERESTED_WANTS_TO_LINK_EXISTING_CA => null,
            self::CLIENT_NOT_INTERESTED_WANTS_TO_USE_ONLY_VA => null,
            self::CLIENT_NOT_INTERESTED_RZP_ISSUE => null,
            self::CLIENT_NOT_INTERESTED_DUE_TO_LONGER_TAT => null,
            self::CANCELLED => null,
            self::ON_HOLD_BY_CLIENT => null,
            self::BUSINESS_IS_NOT_OPERATIONAL => null,
            self::NEGATIVE_PROFILE_SVR_ISSUE => null,
            self::INCOMPLETE_KYC => null,
            self::NOT_SERVICEABLE => null,
            self::UNSUPPORTED_RZP_BUSINESS_TYPE_MODEL => null,
            self::ENTITY_CHANGE_IN_PROGRESS => null,
            self::CC_OD_WITH_OTHER_BANK => null,
            self::CLIENT_PROCEEDING_WITH_DIFFERENT_RZP_MID => null,
            self::CA_OPENED_ORGANICALLY => null,
            self::RM_DELAYS_IN_ACCOUNT_OPENING => null,
            self::OTHER => null,
        ]
    ];

    public static $internallyEditStatuses = [
        self::CREATED,
        self::PICKED,
        self::INITIATED,
        self::VERIFICATION_CALL,
        self::DOC_COLLECTION,
        self::ACCOUNT_OPENING,
        self::API_ONBOARDING,
        self::ACCOUNT_ACTIVATION,
        self::PROCESSED,
        self::PROCESSING,
        self::UNSERVICEABLE,
        self::REJECTED,
        self::CANCELLED,
        self::ARCHIVED,
        self::ACTIVATED,
        self::TERMINATED
    ];

    /**
     * @var array
     * This contains a status map that keeps mapping of an external status
     * to internal status. External signifies the status as understood by the Product.
     * Internal signifies the status as understood by BE.
     *
     * This is used in Admin batch upload when Ops/Sales/Bank teams use a
     * csv to upload change in statuses in bulk.
     *
     * CA Activated and CA Opened are intentionally left out of this list
     * to prevent change to these states, which should only be allowed via webhook/
     * manual activation operation via admin dashboard.
     */
    public static $externalToInternalStatusMap = [
        self::APPLICATION_RECEIVED          => self::CREATED,
        self::RAZORPAY_PROCESSING           => self::PICKED,
        self::SENT_TO_BANK                  => self::INITIATED,
        self::BANK_PROCESSING               => self::PROCESSING,
        self::CA_OPENED                     => self::PROCESSED,
        self::MERCHANT_CANCELLED            => self::CANCELLED,
        self::TEMP_UNSERVICEABLE            => self::UNSERVICEABLE,
        self::BANK_REJECTED                 => self::REJECTED,
        self::ARCHIVED_EXTERNAL             => self::ARCHIVED,
        self::TERMINATED_EXTERNAL         => self::TERMINATED,
        self::VERIFICATION_CALL_EXTERNAL    => self::VERIFICATION_CALL,
        self::DOC_COLLECTION_EXTERNAL       => self::DOC_COLLECTION,
        self::ACCOUNT_OPENING_EXTERNAL      => self::ACCOUNT_OPENING,
        self::API_ONBOARDING_EXTERNAL       => self::API_ONBOARDING,
        self::ACCOUNT_ACTIVATION_EXTERNAL   => self::ACCOUNT_ACTIVATION,
        self::CA_ACTIVATED                  => self::ACTIVATED
    ];

    public static $allowedExternalStatuses = [
        self::APPLICATION_RECEIVED,
        self::RAZORPAY_PROCESSING,
        self::SENT_TO_BANK,
        self::BANK_PROCESSING,
        self::CA_OPENED,
        self::VERIFICATION_CALL_EXTERNAL,
        self::DOC_COLLECTION_EXTERNAL,
        self::ACCOUNT_OPENING_EXTERNAL,
        self::API_ONBOARDING_EXTERNAL,
        self::ACCOUNT_ACTIVATION_EXTERNAL,
        self::MERCHANT_CANCELLED,
        self::TEMP_UNSERVICEABLE,
        self::BANK_REJECTED,
        self::ARCHIVED_EXTERNAL,
        self::TERMINATED_EXTERNAL
    ];

    public static $externalToInternalSubStatusMap = [
        self::NEEDS_CLARIFICATION_FROM_SALES_EXTERNAL     => self::NEEDS_CLARIFICATION_FROM_SALES,
        self::DOCS_WALK_THROUGH_PENDING_EXTERNAL          => self::DOCS_WALK_THROUGH_PENDING,
        self::MERCHANT_NOT_AVAILABLE_EXTERNAL             => self::MERCHANT_NOT_AVAILABLE,
        self::MERCHANT_PREPARING_DOCS_EXTERNAL            => self::MERCHANT_PREPARING_DOCS,
        self::READY_TO_SEND_TO_BANK_EXTRENAL              => self::READY_TO_SEND_TO_BANK,
        self::BANK_TO_PICKUP_DOCS_EXTERNAL                => self::BANK_TO_PICKUP_DOCS,
        self::NEEDS_CLARIFICATION_FROM_RZP_EXTERNAL       => self::NEEDS_CLARIFICATION_FROM_RZP,
        self::BANK_PICKED_UP_DOCS_EXTERNAL                => self::BANK_PICKED_UP_DOCS,
        self::DISCREPANCY_IN_DOCS_EXTERNAL                => self::DISCREPANCY_IN_DOCS,
        self::BANK_OPENED_ACCOUNT_EXTERNAL                => self::BANK_OPENED_ACCOUNT,
        self::API_ONBOARDING_PENDING_EXTERNAL             => self::API_ONBOARDING_PENDING,
        self::API_ONBOARDING_INITIATED_EXTERNAL           => self::API_ONBOARDING_INITIATED,
        self::API_ONBOARDING_IN_PROGRESS_EXTERNAL         => self::API_ONBOARDING_IN_PROGRESS,
        self::NONE_EXTERNAL                               => self::NONE,
        self::UPI_ACTIVATED_EXTERNAL                      => self::UPI_ACTIVATED,
        self::OTHER_EXTERNAL                              => self::OTHER,
        self::UNSERVICEABLE__PINCODE_EXTERNAL             => self::UNSERVICEABLE__PINCODE,
        self::NOT_SERVICEABLE_EXTERNAL                    => self::NOT_SERVICEABLE,
        self::NEGATIVE_PROFILE_SVR_ISSUE_EXTERNAL         => self::NEGATIVE_PROFILE_SVR_ISSUE,
        self::UPI_CREDS_PENDING_EXTERNAL                  => self::UPI_CREDS_PENDING,
        self::CANCELLED_EXTERNAL                          => self::CANCELLED,
        self::CUSTOMER_NOT_RESPONDING_EXTERNAL            => self::CUSTOMER_NOT_RESPONDING,
        self::FOLLOW_UP_REQUESTED_BY_MERCHANT_EXTERNAL    => self::FOLLOW_UP_REQUESTED_BY_MERCHANT,
        self::VISIT_DUE_EXTERNAL                          => self::VISIT_DUE,
        self::PICKED_UP_DOCS_EXTERNAL                     => self::PICKED_UP_DOCS,
        self::IR_IN_DISCREPANCY_EXTERNAL                  => self::IR_IN_DISCREPANCY,
        self::IN_REVIEW_EXTERNAL                          => self::IN_REVIEW,
        self::CA_OPENED_SUB_STATUS_EXTERNAL               => self::CA_OPENED_SUB_STATUS,
        self::FOLLOW_UP_API_DOCS_UNAVAILABLE_EXTERNAL     => self::FOLLOW_UP_API_DOCS_UNAVAILABLE,

        'null'                                        => null
    ];

    // Used to automatically move lead to next status
    public static $statusToTerminalSubStatusMap = [
        self::VERIFICATION_CALL => [
            self::ASSIGNED_TO_BRANCH,
            self::ASSIGNED_TO_PCARM,
            self::ASSIGNED_TO_INSIGNIA,
        ],
        self::DOC_COLLECTION => [
            self::PICKED_UP_DOCS,
        ],
        // self::ACCOUNT_OPENING => [
        //     self::CA_OPENED_SUB_STATUS,
        // ],
        self::API_ONBOARDING => [
            self::API_IR_CLOSED,
        ],
        // self::ACCOUNT_ACTIVATION => [
        //     self::CA_ACTIVATED_SUB_STATUS,
        // ],
        self::ACTIVATED => [
            self::UPI_ACTIVATED,
        ],
    ];

    // Ideal sequence of status for normal cases
    public static $statusSequence = [
        self::CREATED,
        self::PICKED,
        self::INITIATED,
        self::VERIFICATION_CALL,
        self::DOC_COLLECTION,
        self::ACCOUNT_OPENING,
        self::API_ONBOARDING,
        self::ACCOUNT_ACTIVATION,
        self::ACTIVATED,
    ];

    /**
     * Used to calculate default assignee when moving from one state to another
     *
     * Return false if default assignee should not be updated
     *
     * Return null or a valid assignee to update the assignee team
     */
    public static function getDefaultAssigneeTeam(?string $status, ?string $subStatus)
    {
        if($status == null)
        {
            return false;
        }

        if(array_key_exists($status, self::$statusAndSubStatusToAssigneeMap) === false)
        {
            return false;
        }

        $subStatuses = self::$statusAndSubStatusToAssigneeMap[$status];

        if(array_key_exists($subStatus, $subStatuses) === false)
        {
            return false;
        }

        return  self::$statusAndSubStatusToAssigneeMap[$status][$subStatus];
    }

    /**
     * Criteria for Parallel assignees for API Registeration
     */
    public static function isStatusUnderParallelAssignee(?string $status) : bool
    {
        if(in_array($status, [
                self::ACCOUNT_OPENING,
                self::API_ONBOARDING,
            ]) === true)
        {
            return true;
        }

        return false;
    }

    public static function hasReachedTerminalSubStatus(string $status, string $subStatus): bool
    {
        if (array_key_exists($status, self::$statusToTerminalSubStatusMap))
        {
            $terminalSubStatuses = self::$statusToTerminalSubStatusMap[$status];

            if (in_array($subStatus, $terminalSubStatuses))
            {
                return true;
            }
        }
        return false;
    }

    public static function getNextStatusInSequence(string $status)
    {
        $i = 0;
        while ($i < sizeof(self::$statusSequence) && self::$statusSequence[$i] !== $status)
        {
            $i++;
        }
        $i++;

        if ($i < sizeof(self::$statusSequence))
        {
            return self::$statusSequence[$i];
        }

        return null;
    }

    public static function getCompletedStages(string $status)
    {
        $completedStages = [];

        if (in_array($status, self::$statusSequence) === false)
        {
            return $completedStages;
        }

        foreach (self::$statusSequence as $stage)
        {
            if ($status === $stage)
            {
                break;
            }
            array_push($completedStages, $stage);
        }

        return $completedStages;
    }

    public static function checkStatusForRevival(string $status)
    {
        return in_array($status, array(self::PICKED, self::INITIATED));
    }

    public static function isValidStatus(string $status = null)
    {
        return in_array($status, self::$statuses);
    }

    public static function isValidSubStatus(string $status = null)
    {
        return in_array($status, self::$subStatuses);
    }

    public static function isValidExternalStatus(string $status)
    {
        return in_array($status, self::$allowedExternalStatuses);
    }

    public static function isValidExternalSubStatus(string $status)
    {
        return in_array($status, array_keys(self::$externalToInternalSubStatusMap));
    }

    public static function validate(string $status = null)
    {
        if (self::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking status ' . $status,
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }

    public static function validateSubStatus(string $subStatus = null)
    {
        if ($subStatus === null)
        {
            return;
        }

        if (self::isValidSubStatus($subStatus) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking SubStatus ' . $subStatus,
                Entity::SUB_STATUS,
                [
                    Entity::SUB_STATUS => $subStatus
                ]);
        }
    }

    public static function validateStatusSubstatusMapping(string $status, $subStatus)
    {
        if ($subStatus === null)
        {
            return;
        }

        $allowedSubStatuses = self::$statusToSubStatusMap[$status];

        if (in_array($subStatus, $allowedSubStatuses) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Substatus '. $subStatus . ' for status ' . $status,
                Entity::SUB_STATUS,
                [
                    Entity::STATUS     => $status,
                    Entity::SUB_STATUS => $subStatus
                ]);
        }
    }

    // This function is not working properly
    // but so many tests are written based on the flawed implementation
    // We can change these tests later, for now writing correct implementation below
    public static function getDetaultSubStatus(string $status)
    {
        if (in_array($status, self::$defaultSubStatus) === true)
        {
            return self::$defaultSubStatus[$status];
        }

        return null;
    }

    public static function getInitialSubStatus(string $status)
    {
        if (array_key_exists($status, self::$defaultSubStatus) === true)
        {
            return self::$defaultSubStatus[$status];
        }
        else
        {
            return null;
        }
    }

    public static function validateExternalStatus(string $status)
    {
        if (self::isValidExternalStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking External status',
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }

    public static function validateExternalSubStatus(string $subStatus)
    {
        if (self::isValidExternalSubStatus($subStatus) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking External subStatus',
                Entity::STATUS,
                [
                    Entity::STATUS => $subStatus
                ]);
        }
    }

    public static function transformFromExternalToInternal(string $status)
    {
        self::validateExternalStatus($status);

        return self::$externalToInternalStatusMap[$status];
    }

    public static function transformFromInternalToExternal(string $status)
    {
        self::validate($status);

        return array_flip(self::$externalToInternalStatusMap)[$status];
    }

    public static function transformSubStatusFromExternalToInternal(string $subStatus)
    {
        self::validateExternalSubStatus($subStatus);

        return self::$externalToInternalSubStatusMap[$subStatus];
    }

    public static function transformSubStatusFromInternalToExternal($subStatus)
    {
        self::validateSubStatus($subStatus);

        // php array_flip() can only flip string and integer
        if ($subStatus === null)
        {
            return 'NULL';
        }

        $externalToInternalMap = array_diff_assoc(self::$externalToInternalSubStatusMap, ['null' => null]);

        return array_flip($externalToInternalMap)[$subStatus] ?? $subStatus;
    }

    /**
     * Sanitize status by replacing underscore with space and capitalizing first letter of each word
     *
     * @param string $status
     */
    public static function sanitizeStatus($status)
    {
        if (empty($status))
        {
            return $status;
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    public static function validatePreviousToCurrentMapping(string $previousStatus, string $currentStatus, string $subStatus=null)
    {
        $blocked = false;

        if ($subStatus !== null)
        {
            if (isset(self::$blockedSubStatusMap[$subStatus])) {
                $blockedStatusList = self::$blockedSubStatusMap[$subStatus];

                if (in_array($currentStatus, $blockedStatusList, true))
                    $blocked = true;
            }
        }

        $nextStatusList = self::$fromToStatusMap[$previousStatus];

        if (in_array($currentStatus, $nextStatusList, true) === false || $blocked)
        {
            throw new BadRequestValidationFailureException(
                sprintf('Status change from %s to %s not permitted',
                    self::transformFromInternalToExternal($previousStatus),
                    self::transformFromInternalToExternal($currentStatus)),
                Entity::STATUS,
                [
                    'current_status'  => $currentStatus,
                    'previous_status' => $previousStatus,

                ]);
        }
    }

    public static function getAll(): array
    {
        return self::$statuses;
    }

    public static function getAllStatusToSubStatusMap(): array
    {
        return self::$statusToSubStatusMap;
    }

    public static function validateInInitialStatuses(string $status)
    {
        $statusList = self::$initialStatuses;

        if (in_array($status, $statusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                'bank status' . $status. 'cannot be saved',
                Entity::BANK_INTERNAL_STATUS,
                [
                    Entity::STATUS               => $status
                ]);
        }
    }

    public static function getActivatedStatuses(): array
    {
        return self::$activatedStatuses;
    }

    /**
     * Calculated lead's due date for a given status and follow-up date for that status
     */
    public static function getBankDueDate($status, $followUpDate)
    {
        if (empty($followUpDate) === true)
        {
            return null;
        }

        $hours = -1;
        switch ($status) {
            case self::VERIFICATION_CALL:
                $hours = 4; // 4 Hours
                break;

            case self::DOC_COLLECTION:
                $hours = 24 * 1; // 1 Day
                break;

            case self::ACCOUNT_OPENING:
                $hours = 24 * 2; // 2 Days
                break;

            case self::API_ONBOARDING:
                $hours = 24 * 2; // 2 Days
                break;

            case self::ACCOUNT_ACTIVATION:
                $hours = 24 * 1; // 1 Day
                break;

            case self::ACTIVATED:
                $hours = 24 * 2; // 2 Days
                break;
        }

        if ($hours == -1)
        {
            return null;
        }
        else
        {
            $followUpDate = new Carbon\Carbon($followUpDate + $hours * 60 * 60);
            if ($followUpDate->isWeekend())
            {
                $followUpDate = $followUpDate->next('Monday')->startOf('Day');
            }
            return $followUpDate->timestamp;
        }
    }

    public static function statusIsTerminal($status)
    {
        return in_array($status, self::$terminalStatuses, true);
    }

    /**
     * TODO: This assumes substatus and other inputs are not edited together, otherwise there will be dirty reads
     * @throws BadRequestValidationFailureException
     */
    public static function validateSubStatusPrerequisites(Entity $bankingAccount, $substatus)
    {
        if (empty($substatus))
        {
            return;
        }

        /** @var Activation\Detail\Entity $activationDetail */
        $activationDetail = optional($bankingAccount)->bankingAccountActivationDetails;

        $additionDetails = json_decode(optional($activationDetail)->getAdditionalDetails() ?? '{}', true);

        switch ($substatus)
        {
            case self::DD_IN_PROGRESS:
                $trackingId = $additionDetails['tracking_id'] ?? null;

                $estimatedDeliveryDate = $additionDetails['docket_estimated_delivery_date'] ?? null;

                if (empty($trackingId) || empty($estimatedDeliveryDate))
                {
                    throw new BadRequestValidationFailureException(
                        'Both Tracking Id and Docket Estimated Delivery Date needs to be filled',
                        Entity::SUB_STATUS,
                        [
                            'tracking_id'                    => $trackingId,
                            'docket_estimated_delivery_date' => $estimatedDeliveryDate
                        ]);
                }

                break;

            case self::DWT_REQUIRED:
                $skipDwt = $additionDetails['skip_dwt'] ?? null;

                if ($skipDwt === 1)
                {
                    throw new BadRequestValidationFailureException(
                        'Skip Dwt should be false or empty',
                        Entity::SUB_STATUS,
                        [
                            'skip_dwt'                    => $skipDwt
                        ]);
                }

                break;

            case self::DWT_COMPLETED:
                $dwtCompletedTimestamp = $additionDetails['dwt_completed_timestamp'] ?? null;

                if (empty($dwtCompletedTimestamp))
                {
                    throw new BadRequestValidationFailureException(
                        'DWT completed timestamp needs to be present',
                        Entity::SUB_STATUS,
                        [
                            'dwt_completed_timestamp'     => $dwtCompletedTimestamp,
                        ]);
                }

                break;

        }
    }

}
