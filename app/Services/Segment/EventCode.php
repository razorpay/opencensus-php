<?php


namespace RZP\Services\Segment;


class EventCode
{
    /*
     * Onboarding Events
     */

    const BVS_IN_SYNC_CALL_RESPONSE             = "Sync mode BVS API Response";
    const RETRY_INPUT_ACTIVATION_FORM   = "Re-entry Input Activation Form";
    const BVS_IN_SYNC_CALL_REQUEST              = "Sync mode BVS API Call";
    const BVS_IN_SYNC_ENABLED             = 'BVS in sync mode qualified';
    const BVS_ASYNC_CALL_RESPONSE             = "Async mode BVS API Response";
    const BVS_ASYNC_CALL_REQUEST              = "Async mode BVS API Call";

    const ACTIVATION_STATUS_CHANGE  = "Activation Status changed";

    const BANKING_ACCOUNT_STATUS_CHANGE = "Banking Account Status Change";

    const DEDUPE                    = 'Dedupe';

    const ADVOCATE_REFERRAL_CREDITS          = 'Advocate Referral Credits';

    const ADVOCATE_REFERRAL          = 'Advocate Referral';

    const MTU_TRANSACTED            = 'MTU Transacted';

    const PURCHASE_EVENT_SENT            = 'Purchase Event Sent';

    const M2M_ENABLED_EXPERIMENT         = 'M2M EXPERIMENT ENABLED';

    const KYC_FORM_SAVED            = 'KYC Form Saved';

    const KYC_STATUS_CHANGE         = 'KYC Status Change';

    const PARTNER_HAVE_COMMISSION   = 'Partner Have Commission';

    const AFFILIATE_ACCOUNT_ADDED   = 'Affiliate Account Added';

    const L1_SUBMISSION             = 'L1 Submission';

    const L2_SUBMISSION             = 'L2 Submission';

    const PAYMENTS_ENABLED           = 'Payments Enabled';

    const SIGNUP_SUCCESS            = 'Signup Success';

    const SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS = 'Signup Email Send Verification Success';

    const WEBSITE_SELF_SERVE_WORKFLOW            = 'Edit website workflow status';

    const TRANSACTION_LIMIT_SELF_SERVE_WORKFLOW  = 'Transaction limit workflow status';

    const EDIT_GSTIN_BVS_RESULT                  = 'Edit gstin bvs result';

    const INVOICES_CREATE_RESULT                 = 'Invoices create result';

    const EDIT_GSTIN_WORKFLOW_STATUS             = 'Edit gstin workflow status';

    const EDIT_GSTIN_WORKFLOW_CREATED            = 'Edit gstin workflow created';

    const ADD_GSTIN_BVS_RESULT                   = 'Add gstin bvs result';

    const ADD_GSTIN_WORKFLOW_STATUS              = 'Add gstin workflow status';

    const ADD_GSTIN_WORKFLOW_CREATED             = 'Add gstin workflow created';

    const BANK_ACCOUNT_UPDATE_WORKFLOW                 = 'Bank account update workflow status';

    const BANK_ACCOUNT_UPDATE_BVS_FUZZY_MATCH_RESULT   = 'bank account fuzzy match result';

    const BANK_ACCOUNT_UPDATE_PENNY_TEST_RESULT        = 'bank account penny test result';

    const BANK_ACCOUNT_UPDATE_WORKFLOW_CREATED         = 'bank account workflow created';

    const CA_ACTIVATED              = 'Current Account Activated';

    const CONTACT_CREATED           = 'Contact Created';

    const FUND_ACCOUNT_ADDED        = 'Fund Account Added';

    const USER_LOGIN                = 'User Login';

    const CA_PAYOUT_PROCESSED       = 'CA Payout Processed';

    const VA_PAYOUT_PROCESSED       = 'VA Payout Processed';

    const X_SIGNUP_SUCCESS            = 'X Signup Success';

    const EVENT_LABELS = [
        self::L1_SUBMISSION                             => "L1 Form Submit",
        self::L2_SUBMISSION                             => "L2 Form Submit",
        self::MTU_TRANSACTED                            => "MTU Transacted",
        self::SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS    => "Email Verify Success"
    ];

    // Milestones
    const IDENTIFY_WEB_ATTRIBUTION  = "Identify Web Attribution";

    const IDENTIFY_APP_ATTRIBUTION  = "Identify App Attribution";

}
