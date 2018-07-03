<?php

namespace RZP\Gateway\Mpi\Enstage;

class Url
{
    const LIVE_DOMAIN   = 'https://exppay.enstage-sas.com';
    const TEST_DOMAIN   = 'https://mpi-expresspay.enstage-uat.com';

    const OTP_GENERATE  = '/MultiMPI/MerchantServer?perform=callOTPRequest';
    const OTP_SUBMIT    = '/MultiMPI/MerchantServer?perform=callOTPValidate';
    const OTP_RESEND    = '/MultiMPI/MerchantServer?perform=callOTPResend';
}
