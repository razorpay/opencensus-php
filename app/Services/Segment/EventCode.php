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
}
