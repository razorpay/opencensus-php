<?php

namespace RZP\Models\BankingAccount;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED           = 'created';       // Application Received
    const PICKED            = 'picked';        // Razorpay Processing
    const INITIATED         = 'initiated';     // Sent to Bank
    const PROCESSING        = 'processing';    // Bank Processing
    const PROCESSED         = 'processed';     // CA Opened
    const CANCELLED         = 'cancelled';     // Merchant Cancelled
    const ACTIVATED         = 'activated';     // CA Activated
    const UNSERVICEABLE     = 'unserviceable'; // Temp Unserviceable
    const REJECTED          = 'rejected';      // Bank Rejected
    const ARCHIVED          = 'archived';


    // External Statuses as interpreted by Product

    const APPLICATION_RECEIVED = 'ApplicationReceived';
    const RAZORPAY_PROCESSING  = 'RazorpayProcessing';
    const SENT_TO_BANK         = 'SentToBank';
    const BANK_PROCESSING      = 'BankProcessing';
    const CA_OPENED            = 'CAOpened';
    const MERCHANT_CANCELLED   = 'MerchantCancelled';
    const CA_ACTIVATED         = 'CAActivated';
    const TEMP_UNSERVICEABLE   = 'TempUnserviceable';
    const BANK_REJECTED        = 'BankRejected';
    const ARCHIVED_EXTERNAL    = 'Archived';

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

    // External Substatuses as inputted by Ops/Sales teams via batch
    const DOCS_WALK_THROUGH_PENDING_EXTERNAL      = 'Docs Walkthrough Pending';
    const NEEDS_CLARIFICATION_FROM_SALES_EXTERNAL = 'Needs clarification from Sales';
    const MERCHANT_NOT_AVAILABLE_EXTERNAL         = 'Merchant is not Available';
    const MERCHANT_PREPARING_DOCS_EXTERNAL        = 'Merchant is preparing Docs';
    const READY_TO_SEND_TO_BANK_EXTRENAL          = 'Ready to send to Bank';
    const BANK_TO_PICKUP_DOCS_EXTERNAL            = 'Bank yet to pick up Docs';
    const BANK_PICKED_UP_DOCS_EXTERNAL            = 'Bank has picked up Docs';
    const RAZORPAY_DEPENDENT_EXTERNAL             = 'Razorpay Dependent';
    const DISCREPANCY_IN_DOCS_EXTERNAL            = 'Discrepancy in Docs';
    const BANK_OPENED_ACCOUNT_EXTERNAL            = 'Bank Opened Account-Webhook Pending';
    const API_ONBOARDING_PENDING_EXTERNAL         = 'API onboarding is Pending on RZP';
    const API_ONBOARDING_INITIATED_EXTERNAL       = 'API onboarding has been initiated by RZP';
    const API_ONBOARDING_IN_PROGRESS_EXTERNAL     = 'API onboarding in Progress';
    const NONE_EXTERNAL                           = 'None';

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
    const PENDING_ON_SALES_DWT_NOT_COMPLETED_MX_NOT_RESPONDING_SPOC_TO_RESCHEDULE         = self::PENDING_ON_SALES_SUB_STRING.'dwt_not_completed_-_mx_not_responding_-_spoc_to_reschedule';
    const PENDING_ON_SALES_UNSUPPORTED_MISMATCH_OF_BIZ_TYPE_ON_ADMIN_DASHBOARD_AND_LMS    = self::PENDING_ON_SALES_SUB_STRING.'unsupported/_mismatch_of_biz_type_on_admin_dashboard_and_lms';

    //
    // Account details can be saved only if the status
    // of banking account is in below array
    //
    public static $allowedStatusForDetails = [self::PROCESSED];

    protected static $initialStatuses = [
        Status::CREATED,
        Status::UNSERVICEABLE,
    ];

    public static $activatedStatuses = [
        self::ACTIVATED,
    ];

    public static $pendingOnSalesBucket = [
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
        self::ACTIVATED
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
            // This is for cases in Neostone where users submit the details in the form
            // but don’t respond when called.
            self::ARCHIVED,
        ],
        self::PICKED => [
            self::INITIATED,
            self::UNSERVICEABLE,
            self::CANCELLED,
            self::PROCESSED,
            self::ARCHIVED,
        ],
        self::INITIATED => [
            self::PROCESSING,
            self::PROCESSED,
            self::CANCELLED,
            self::REJECTED,
            self::ARCHIVED
        ],
        self::PROCESSING => [
            self::PROCESSED,
            self::CANCELLED,
            self::REJECTED,
            self::ARCHIVED,
        ],
        self::PROCESSED => [
            self::ACTIVATED,
            // Sometimes leads drop off after CA is opened.
            self::ARCHIVED,
        ],
        self::UNSERVICEABLE => [
            self::PICKED,
        ],

        self::ACTIVATED => [
            self::ARCHIVED
        ],
        self::CANCELLED => [
            // Sometimes Sales team is able to revive leads who
            // had earlier cancelled their request. This is to
            // restart the process.
            self::PICKED,
        ],
        self::REJECTED  => [
            // Temporarily allowing this transition because of
            // https://razorpay.slack.com/archives/CRA6TGU8H/p1603954629097600?thread_ts=1603779164.072600&cid=CRA6TGU8H
            self::PROCESSED
        ],
        self::ARCHIVED  => [
            self::PICKED,
            self::INITIATED,
            self::PROCESSING,
            self::PROCESSED,
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
        self::OTHER,
        self::NONE,
    ];

    protected static $defaultSubStatus = [
        self::PROCESSED => self::API_ONBOARDING_PENDING
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

        self::ACTIVATED => [],
        self::CANCELLED => [
        ],
        self::REJECTED  => [
        ],
        self::ARCHIVED  => [
        ]
    ];

    public static $internallyEditStatuses = [
        self::CREATED,
        self::PICKED,
        self::INITIATED,
        self::PROCESSED,
        self::PROCESSING,
        self::UNSERVICEABLE,
        self::REJECTED,
        self::CANCELLED,
        self::ARCHIVED,
        self::ACTIVATED,
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
        self::APPLICATION_RECEIVED => self::CREATED,
        self::RAZORPAY_PROCESSING  => self::PICKED,
        self::SENT_TO_BANK         => self::INITIATED,
        self::BANK_PROCESSING      => self::PROCESSING,
        self::CA_OPENED            => self::PROCESSED,
        self::MERCHANT_CANCELLED   => self::CANCELLED,
        self::TEMP_UNSERVICEABLE   => self::UNSERVICEABLE,
        self::BANK_REJECTED        => self::REJECTED,
        self::ARCHIVED_EXTERNAL    => self::ARCHIVED,
        self::CA_ACTIVATED         => self::ACTIVATED
    ];

    public static $allowedExternalStatuses = [
        self::APPLICATION_RECEIVED,
        self::RAZORPAY_PROCESSING,
        self::SENT_TO_BANK,
        self::BANK_PROCESSING,
        self::CA_OPENED,
        self::MERCHANT_CANCELLED,
        self::TEMP_UNSERVICEABLE,
        self::BANK_REJECTED,
        self::ARCHIVED_EXTERNAL
    ];

    public static $externalToInternalSubStatusMap = [
        self::NEEDS_CLARIFICATION_FROM_SALES_EXTERNAL => self::NEEDS_CLARIFICATION_FROM_SALES,
        self::DOCS_WALK_THROUGH_PENDING_EXTERNAL      => self::DOCS_WALK_THROUGH_PENDING,
        self::MERCHANT_NOT_AVAILABLE_EXTERNAL         => self::MERCHANT_NOT_AVAILABLE,
        self::MERCHANT_PREPARING_DOCS_EXTERNAL        => self::MERCHANT_PREPARING_DOCS,
        self::READY_TO_SEND_TO_BANK_EXTRENAL          => self::READY_TO_SEND_TO_BANK,
        self::BANK_TO_PICKUP_DOCS_EXTERNAL            => self::BANK_TO_PICKUP_DOCS,
        self::RAZORPAY_DEPENDENT_EXTERNAL             => self::NEEDS_CLARIFICATION_FROM_RZP,
        self::BANK_PICKED_UP_DOCS_EXTERNAL            => self::BANK_PICKED_UP_DOCS,
        self::DISCREPANCY_IN_DOCS_EXTERNAL            => self::DISCREPANCY_IN_DOCS,
        self::BANK_OPENED_ACCOUNT_EXTERNAL            => self::BANK_OPENED_ACCOUNT,
        self::API_ONBOARDING_PENDING_EXTERNAL         => self::API_ONBOARDING_PENDING,
        self::API_ONBOARDING_INITIATED_EXTERNAL       => self::API_ONBOARDING_INITIATED,
        self::API_ONBOARDING_IN_PROGRESS_EXTERNAL     => self::API_ONBOARDING_IN_PROGRESS,
        self::NONE_EXTERNAL                           => self::NONE,
        'null'                                        => null
    ];


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

    public static function getDetaultSubStatus(string $status)
    {
        if (in_array($status, self::$defaultSubStatus) === true)
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

        return array_flip($externalToInternalMap)[$subStatus];
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
}
