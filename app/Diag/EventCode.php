<?php

namespace RZP\Diag;

class EventCode
{
    // order events
    const ORDER_CREATION_INITIATED                      = 'order.creation.initiated';
    const ORDER_CREATION_PROCESSED                      = 'order.creation.processed';

    // payment flow events
    const PAYMENT_CREATION_INITIATED                    = 'payment.creation.initiated';
    const PAYMENT_INPUT_VALIDATIONS_INITIATED           = 'payment.input.validations.initiated';
    const PAYMENT_INPUT_VALIDATIONS_PROCESSED           = 'payment.input.validations.processed';
    const PAYMENT_CARDSAVING_INITIATED                  = 'payment.cardsaving.initiated';
    const PAYMENT_CARDSAVING_PROCESSED                  = 'payment.cardsaving.processed';
    const PAYMENT_INPUT_VALIDATIONS2_INITIATED          = 'payment.input.validations.initiated';
    const PAYMENT_INPUT_VALIDATIONS2_PROCESSED          = 'payment.input.validations.processed';
    const PAYMENT_RISKCHECK_INITIATED                   = 'payment.riskcheck.initiated';
    const PAYMENT_RISKCHECK_PROCESSED                   = 'payment.riskcheck.processed';
    const PAYMENT_TERMINAL_SELECTION_INITIATED          = 'payment.terminal.selection.initiated';
    const PAYMENT_TERMINAL_SELECTION_PROCESSED          = 'payment.terminal.selection.processed';
    const PAYMENT_CREATION_PROCESSED                    = 'payment.creation.processed';
    const PAYMENT_CREATE_REDIRECT_RESPONSE_SENT         = 'payment.create.redirect.response.sent';
    const PAYMENT_CREATE_REDIRECT_INITIATED             = 'payment.create.redirect.initiated';
    const PAYMENT_CREATE_REDIRECT_PROCESSED             = 'payment.create.redirect.processed';
    const PAYMENT_AUTHENTICATION_INITIATED              = 'payment.authentication.initiated';
    const PAYMENT_AUTHENTICATION_OTP_GENERATE_INITIATED = 'payment.authentication.otp.generate.initiated';
    const PAYMENT_AUTHENTICATION_OTP_GENERATE_PROCESSED = 'payment.authentication.otp.generate.processed';
    const PAYMENT_AUTHENTICATION_OTP_RESEND_INITIATED   = 'payment.authentication.otp.resend.initiated';
    const PAYMENT_AUTHENTICATION_OTP_RESEND_PROCESSED   = 'payment.authentication.otp.resend.processed';
    const PAYMENT_AUTHENTICATION_OTP_SUBMIT_INITIATED   = 'payment.authentication.otp.submit.initiated';
    const PAYMENT_AUTHENTICATION_OTP_SUBMIT_PROCESSED   = 'payment.authentication.otp.submit.processed';
    const PAYMENT_AUTHENTICATION_HEADLESS_INITIATED     = 'payment.authentication.headless.initiated';
    const PAYMENT_AUTHENTICATION_HEADLESS_PROCESSED     = 'payment.authentication.headless.processed';
    const PAYMENT_AUTHENTICATION_3DS_REDIRECT_INITIATED = 'payment.authentication.3ds.redirect.initiated';
    const PAYMENT_AUTHORIZATION_INITIATED               = 'payment.authorization.initiated';
    const PAYMENT_AUTHENTICATION_2FA_URL_SENT           = 'payment.authentication.2fa.url.sent';
    const PAYMENT_AUTHORIZATION_DROPPED                 = 'payment.authorization.dropped';
    const PAYMENT_AUTHORIZATION_PROCESSED               = 'payment.authorization.processed';
    const PAYMENT_CALLBACK_INITIATED                    = 'payment.callback.initiated';
    const PAYMENT_S2S_CALLBACK_INITIATED                = 'payment.s2s.callback.initiated';
    const PAYMENT_REDIRECT_CALLBACK_INITIATED           = 'payment.redirect.callback.initiated';
    const PAYMENT_CAPTURE_INITIATED                     = 'payment.capture.initiated';
    const PAYMENT_PRICING_CALCULATIONS_INITIATED        = 'payment.pricing.calculations.initiated';
    const PAYMENT_PRICING_CALCULATIONS_PROCESSED        = 'payment.pricing.calculations.processed';
    const PAYMENT_CAPTURE_PROCESSED                     = 'payment.capture.processed';
    const PAYMENT_CREATE_REQUEST_PROCESSED              = 'payment.create.request.processed';
    const PAYMENT_RESPONSE_SENT                         = 'payment.response.sent';

    // payment verification
    const PAYMENT_VERIFICATION_INITIATED                = 'payment.verification.initiated';
    const PAYMENT_VERIFICATION_PROCESSED                = 'payment.verification.processed';
}
