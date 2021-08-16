<?php


namespace RZP\Services\Segment;


class EventCode
{
    /*
     * Onboarding Events
     */

    const ACTIVATION_STATUS_CHANGE  = "Activation Status toggle";

    const DEDUPE                    = 'Dedupe';

    const MTU_TRANSACTED            = 'MTU Transacted';

    const KYC_FORM_SAVED            = 'KYC Form Saved';

    const L1_SUBMISSION             = 'L1 Submission';

    const L2_SUBMISSION             = 'L2 Submission';

    const SIGNUP_SUCCESS            = 'Signup Success';

    const SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS = 'Signup Email Send Verification Success';


    const EVENT_LABELS = [
        self::L1_SUBMISSION                             => "L1 Form Submit",
        self::L2_SUBMISSION                             => "L2 Form Submit",
        self::MTU_TRANSACTED                            => "MTU Transacted",
        self::SIGNUP_EMAIL_SEND_VERIFICATION_SUCCESS    => "Email Verify Success"
    ];
}
