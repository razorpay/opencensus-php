<?php

namespace RZP\Gateway\Mpi\Enstage;

use RZP\Gateway\Base\Action as Base;

class Action extends Base
{
    const OTP_GENERATE      = 'otp_generate';

    const OTP_RESEND        = 'otp_resend';

    const OTP_SUBMIT        = 'otp_submit';
}
