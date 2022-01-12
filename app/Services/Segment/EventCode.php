<?php


namespace RZP\Services\Segment;


class EventCode
{
    /*
     * Onboarding Events
     */

    const ACTIVATION_STATUS_CHANGE  = "Activation Status changed";

    const BANKING_ACCOUNT_STATUS_CHANGE = "Banking Account Status Change";

    const DEDUPE                    = 'Dedupe';

    const ADVOCATE_REFERRAL_CREDITS          = 'Advocate Referral Credits';

    const ADVOCATE_REFERRAL          = 'Advocate Referral';

    const MTU_TRANSACTED            = 'MTU Transacted';

    const PURCHASE_EVENT_SENT            = 'Purchase Event Sent';

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
