<?php

namespace RZP\Diag;

class EventCode
{
    // order events
    const ORDER_CREATION_INITIATED                      = [
        'group' => 'initiation',
        'name'  => 'order.creation.initiated'
    ];

    const ORDER_CREATION_PROCESSED                      = [
        'group' => 'initiation',
        'name'  => 'order.creation.processed'
    ];

    // payment flow events
    const PAYMENT_CREATION_INITIATED                    = [
        'group' => 'initiation',
        'name'  => 'payment.creation.initiated'
    ];

    const PAYMENT_INPUT_VALIDATIONS_INITIATED           = [
        'group' => 'internal_checks',
        'name'  => 'payment.input.validations.initiated'
    ];

    const PAYMENT_INPUT_VALIDATIONS_PROCESSED           = [
        'group' => 'internal_checks',
        'name'  => 'payment.input.validations.processed'
    ];

    const PAYMENT_CARDSAVING_INITIATED                  = [
        'group' => 'internal_checks',
        'name'  => 'payment.cardsaving.initiated'
    ];

    const PAYMENT_CARDSAVING_PROCESSED                  = [
        'group' => 'internal_checks',
        'name'  => 'payment.cardsaving.processed'
    ];

    const PAYMENT_INPUT_VALIDATIONS2_INITIATED          = [
        'group' => 'internal_checks',
        'name'  => 'payment.input.validations2.initiated'
    ];

    const PAYMENT_INPUT_VALIDATIONS2_PROCESSED          = [
        'group' => 'internal_checks',
        'name'  => 'payment.input.validations2.processed'
    ];

    const PAYMENT_RISKCHECK_INITIATED                   = [
        'group' => 'internal_checks',
        'name'  => 'payment.riskcheck.initiated'
    ];

    const PAYMENT_RISKCHECK_PROCESSED                   = [
        'group' => 'internal_checks',
        'name'  => 'payment.riskcheck.processed'
    ];

    const PAYMENT_TERMINAL_SELECTION_INITIATED          = [
        'group' => 'internal_checks',
        'name'  => 'payment.terminal.selection.initiated'
    ];

    const PAYMENT_TERMINAL_SELECTION_PROCESSED          = [
        'group' => 'internal_checks',
        'name'  => 'payment.terminal.selection.processed'
    ];

    const PAYMENT_CREATION_PROCESSED                    = [
        'group' => 'payment_creation',
        'name'  => 'payment.creation.processed'
    ];

    const PAYMENT_CREATE_REDIRECT_RESPONSE_SENT         = [
        'group' => 'payment_creation',
        'name'  => 'payment.create.redirect.response.sent'
    ];

    const PAYMENT_CREATE_REDIRECT_INITIATED             = [
        'group' => 'payment_creation',
        'name'  => 'payment.create.redirect.initiated'
    ];

    const PAYMENT_CREATE_REDIRECT_PROCESSED             = [
        'group' => 'authentication',
        'name'  => 'payment.create.redirect.processed'
    ];

    const PAYMENT_AUTHENTICATION_INITIATED              = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.initiated'
    ];

    const PAYMENT_AUTHENTICATION_ENROLLMENT_INITIATED              = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.enrollment.initiated'
    ];

    const PAYMENT_AUTHENTICATION_ENROLLMENT_PROCESSED              = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.enrollment.processed'
    ];

    const PAYMENT_AUTHENTICATION_PROCESSED              = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.processed'
    ];

    const PAYMENT_AUTHENTICATION_OTP_RESEND_INITIATED   = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.otp.resend.initiated'
    ];

    const PAYMENT_AUTHENTICATION_OTP_RESEND_PROCESSED   = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.otp.resend.processed'
    ];

    const PAYMENT_AUTHENTICATION_OTP_SUBMIT_INITIATED   = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.otp.submit.initiated'
    ];

    const PAYMENT_AUTHENTICATION_OTP_SUBMIT_PROCESSED   = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.otp.submit.processed'
    ];

    const PAYMENT_AUTHENTICATION_HEADLESS_INITIATED     = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.headless.initiated'
    ];

    const PAYMENT_AUTHENTICATION_HEADLESS_PROCESSED     = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.headless.processed'
    ];

    const PAYMENT_AUTHENTICATION_3DS_REDIRECT_INITIATED = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.3ds.redirect.initiated'
    ];

    const PAYMENT_AUTHENTICATION_2FA_URL_SENT           = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.2fa.url.sent'
    ];

    const PAYMENT_AUTHORIZATION_INITIATED               = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.initiated'
    ];

    const PAYMENT_AUTHORIZATION_DROPPED                 = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.dropped'
    ];

    const PAYMENT_AUTHORIZATION_PROCESSED               = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.processed'
    ];

    const PAYMENT_CALLBACK_INITIATED                    = [
        'group' => 'authorization',
        'name'  => 'payment.callback.initiated'
    ];

    const PAYMENT_S2S_CALLBACK_INITIATED                = [
        'group' => 'authorization',
        'name'  => 'payment.s2s.callback.initiated'
    ];

    const PAYMENT_REDIRECT_CALLBACK_INITIATED           = [
        'group' => 'authorization',
        'name'  => 'payment.redirect.callback.initiated'
    ];

    const PAYMENT_CAPTURE_INITIATED                     = [
        'group' => 'capture',
        'name'  => 'payment.capture.initiated'
    ];

    const PAYMENT_PRICING_CALCULATIONS_INITIATED        = [
        'group' => 'capture',
        'name'  => 'payment.pricing.calculations.initiated'
    ];

    const PAYMENT_PRICING_CALCULATIONS_PROCESSED        = [
        'group' => 'capture',
        'name'  => 'payment.pricing.calculations.processed'
    ];

    const PAYMENT_CAPTURE_PROCESSED                     = [
        'group' => 'capture',
        'name'  => 'payment.capture.processed'
    ];

    const PAYMENT_CREATE_REQUEST_PROCESSED              = [
        'group' => 'payment_create_response',
        'name'  => 'payment.create.request.processed'
    ];

    const PAYMENT_RESPONSE_SENT                         = [
        'group' => 'payment_response',
        'name'  => 'payment.response.sent'
    ];

    // payment verification
    const PAYMENT_VERIFICATION_INITIATED                = [
        'group' => 'verification',
        'name'  => 'payment.verification.initiated'
    ];

    const PAYMENT_VERIFICATION_PROCESSED                = [
        'group' => 'verification',
        'name'  => 'payment.verification.processed'
    ];

    const PAYMENT_AUTHENTICATION_OMNICHANNEL_REQUEST_INITIATED = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.omnichannel.request.initiated'
    ];

    const PAYMENT_AUTHENTICATION_OMNICHANNEL_REQUEST_PROCESSED = [
        'group' => 'authentication',
        'name'  => 'payment.authentication.omnichannel.request.processed'
    ];

    //Settlement flow events
    const TRANSACTION_SETTLED_AT_UPDATE = [
        'group' => 'initiation',
        'name'  => 'transaction.settled.at.update'
    ];

    const SETTLEMENT_CREATION_INITIATED = [
        'group' => 'initiation',
        'name'  => 'settlement.creation.initiated'
    ];

    const SETTLEMENT_CREATION_SUCCESS = [
        'group' => 'success',
        'name'  => 'settlement.creation.success'
    ];

    const SETTLEMENT_CREATION_FAILED = [
        'group' => 'failure',
        'name'  => 'settlement.creation.failed'
    ];

    const FTA_CREATION_INITIATED = [
        'group' => 'initiation',
        'name'  => 'fta.creation.initiated'
    ];

    const FTA_CREATION_SUCCESS = [
        'group' => 'success',
        'name'  => 'fta.creation.initiated'
    ];

    const FTA_CREATION_FAILED = [
        'group' => 'failure',
        'name'  => 'fta.creation.failed'
    ];

    const BATCH_FUND_TRANSFER_CREATION_INITIATED = [
        'group' => 'initiated',
        'name'  => 'batchFta.creation.initiated'
    ];

    const BATCH_FUND_TRANSFER_CREATION_SUCCESS = [
        'group' => 'success',
        'name'  => 'batchFta.creation.success'
    ];

    const BATCH_FUND_TRANSFER_CREATION_FAILED = [
        'group' => 'failure',
        'name'  => 'batchFta.creation.failed'
    ];

    const BEAM_FILE_PUSH_SUCCESS = [
        'group' => 'success',
        'name'  => 'beam.push.success'
    ];

    const BEAM_FILE_PUSH_RETRY = [
        'group' => 'retry',
        'name'  => 'beam.push.retry'
    ];

    const BEAM_FILE_PUSH_FAILED = [
        'group' => 'failure',
        'name'  => 'beam.push.failed'
    ];

    const FTA_UTR_UPDATED = [
        'group' => 'update',
        'name'  => 'fta.utr.updated'
    ];

    const FTA_STATUS_UPDATED = [
        'group' => 'update',
        'name'  => 'fta.status.updated'
    ];

    const SETTLEMENT_STATUS_UPDATED = [
        'group' => 'update',
        'name'  => 'settlement.status.updated'
    ];

    const REVERSE_FEED_RECEIVED = [
        'group' => 'receive',
        'name'  => 'reverse.feed.received'
    ];

}
