<?php


namespace RZP\Services\Segment;


class EventCode
{
    /*
     * Onboarding Events
     */

    const BVS_IN_SYNC_CALL_RESPONSE     = "Sync mode BVS API Response";
    const RETRY_INPUT_ACTIVATION_FORM   = "Re-entry Input Activation Form";
    const BVS_IN_SYNC_CALL_REQUEST      = "Sync mode BVS API Call";
    const BVS_IN_SYNC_ENABLED           = 'BVS in sync mode qualified';
    const BVS_ASYNC_CALL_RESPONSE       = "Async mode BVS API Response";
    const BVS_ASYNC_CALL_REQUEST        = "Async mode BVS API Call";

    const ACTIVATION_STATUS_CHANGE = "Activation Status changed";
    const SUBMERCHANT_ACTIVATED = "Submerchant Activated";

    const X_BANKING_ACCOUNT_STATUS_CHANGE_V2 = "X Banking Account Status Change V2";
    const BANKING_ACCOUNT_DOCUMENT_VERIFICATION_STATUS = "Banking Account Document Verification Status";

    const DEDUPE                    = 'Dedupe';

    //M2M events
    const ADVOCATE_REFERRAL_CREDITS = 'Advocate Referral Credits';
    const ADVOCATE_REFERRAL = 'Advocate Referral';
    const MTU_TRANSACTED = 'MTU Transacted';
    const MONTH_FIRST_MTU_TRANSACTED = 'Month First MTU';
    const PURCHASE_EVENT_SENT = 'Purchase Event Sent';
    const M2M_ENABLED = 'M2M  ENABLED';
    const M2M_ENABLED_EXPERIMENT = 'M2M EXPERIMENT ENABLED';

    const ONE_MONTH_POST_MTU = '1 Month Post MTU';

    const AMP_EMAIL_L1_SUBMISSION = 'AMPEmail L1Submission';

    const KYC_FORM_SAVED = 'KYC Form Saved';

    const KYC_STATUS_CHANGE = 'KYC Status Change';

    const PARTNER_HAVE_COMMISSION = 'Partner Have Commission';

    const AFFILIATE_ACCOUNT_ADDED = 'Affiliate Account Added';

    const SUBMERCHANT_FIRST_TRANSACTION = 'Submerchant First Transaction';

    const L1_SUBMISSION = 'L1 Submission';

    const L2_SUBMISSION = 'L2 Submission';

    const PAYMENTS_ENABLED = 'Payments Enabled';

    const SIGNUP_ATTRIBUTED = 'Sign Up Attributed';

    const SIGNUP_SUCCESS = 'Signup Success';

    const SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS = 'Signup Email Send Verification Success';

    const WEBSITE_SELF_SERVE_WORKFLOW = 'Edit website workflow status';

    const TRANSACTION_LIMIT_SELF_SERVE_WORKFLOW = 'Transaction limit workflow status';

    const EDIT_GSTIN_BVS_RESULT = 'Edit gstin bvs result';

    const INVOICES_CREATE_RESULT = 'Invoices create result';

    const EDIT_GSTIN_WORKFLOW_STATUS = 'Edit gstin workflow status';

    const EDIT_GSTIN_WORKFLOW_CREATED = 'Edit gstin workflow created';

    const ADD_GSTIN_BVS_RESULT = 'Add gstin bvs result';

    const ADD_GSTIN_WORKFLOW_STATUS = 'Add gstin workflow status';

    const ADD_GSTIN_WORKFLOW_CREATED = 'Add gstin workflow created';

    const BANK_ACCOUNT_UPDATE_WORKFLOW = 'Bank account update workflow status';

    const BANK_ACCOUNT_UPDATE_BVS_FUZZY_MATCH_RESULT = 'bank account fuzzy match result';

    const BANK_ACCOUNT_UPDATE_PENNY_TEST_RESULT = 'bank account penny test result';

    const BANK_ACCOUNT_UPDATE_WORKFLOW_CREATED = 'bank account workflow created';

    const SELF_SERVE_SUCCESS = 'Self Serve Success';

    const CA_ACTIVATED = 'Current Account Activated';

    const CONTACT_CREATED = 'Contact Created';

    const FUND_ACCOUNT_ADDED = 'Fund Account Added';

    const USER_LOGIN = 'User Login';

    const CA_PAYOUT_PROCESSED = 'CA Payout Processed';

    const VA_PAYOUT_PROCESSED = 'VA Payout Processed';

    const X_SIGNUP_SUCCESS = 'X Signup Success';

    const APPSFLYER_UNINSTALL = 'Uninstall';

    const EVENT_LABELS = [
        self::L1_SUBMISSION => "L1 Form Submit",
        self::L2_SUBMISSION => "L2 Form Submit",
        self::MTU_TRANSACTED => "MTU Transacted",
        self::SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS => "Email Verify Success"
    ];

    // Milestones
    const IDENTIFY_WEB_ATTRIBUTION = "Identify Web Attribution";

    const IDENTIFY_APP_ATTRIBUTION = "Identify App Attribution";

    const INTERNATIONAL_PAYMENTS_ENABLED =  "International Payments Enabled";

    const PARTNER_ADDED_FIRST_SUBMERCHANT =  "Partner Added First Submerchant";

    const OFFERMTU_TARGETED_MERCHANT = "OfferMtu Targeted Merchant";

    //Legal Consents
    const AGREEMENT_CREATION_REQUEST = "Clickwrap Agreement Creation Request";
    const AGREEMENT_CREATION_RESPONSE = "Clickwrap Agreement Creation Response";

    //UPI Terminal creation
    const UPI_WRAPPER_REQUESTED = "UPI Wrapper Requested";

    // Settlement Clearance

    const ACQ_SETTLEMENT_CLEARANCE_WORKFLOW_CREATED = 'Acq Settlement Clearance Workflow Created';

    const ACQ_SETTLEMENT_CLEARANCE_FAILED           = 'Acq Settlement Clearance Failed' ;

    const MERCHANT_FUNDS_PAYMENT_STATUS = 'Merchant Funds And Payment Status';

    //Lead Score Calculation
    const LEAD_SCORE_CALCULATED = "Lead Score Calculated";
}
