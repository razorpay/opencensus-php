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

    const REARCH_PAYMENT_CREATION_INITIATED             = [
        'group' => 'initiation',
        'name'  => 'rearch.payment.creation.initiated'
    ];

    const PAYMENT_INPUT_VALIDATIONS_INITIATED           = [
        'group' => 'internal_checks',
        'name'  => 'payment.input.validations.initiated'
    ];

    const PAYMENT_INPUT_VALIDATIONS_PROCESSED           = [
        'group' => 'internal_checks',
        'name'  => 'payment.input.validations.processed'
    ];

    const PAYMENT_CREATION_RESPAWN                      = [
        'group' => 'initiation',
        'name'  => 'payment.creation.respawn'
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

    const PAYMENT_TERMINALS_RECEIVED_FROM_SMART_ROUTING    = [
        'group' => 'internal_checks',
        'name'  => 'payment.terminals.received.from.smart.routing'
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

    const PAYMENT_CREATE_OTP_GENERATE_INITIATED             = [
        'group' => 'payment_creation',
        'name'  => 'payment.create.otp.generate.initiated'
    ];

    const PAYMENT_CREATE_OTP_GENERATE_PROCESSED             = [
        'group' => 'authentication',
        'name'  => 'payment.create.otp.generate.processed'
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

    const PAYMENT_PENDING_PROCESSED                     = [
        'group' => 'pending',
        'name'  => 'payment.pending.processed'
    ];

    const PAYMENT_AUTHORIZATION_INITIATED               = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.initiated'
    ];

    const PAYMENT_AUTHORIZATION_DROPPED                 = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.dropped'
    ];

    const PAYMENT_AUTHORIZATION_FAILED                  = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.failed'
    ];

    const PAYMENT_AUTHORIZATION_PROCESSED               = [
        'group' => 'authorization',
        'name'  => 'payment.authorization.processed'
    ];

    const PAYMENT_CRON_TIMEOUT_INITIATED               = [
        'group' => 'timeout',
        'name'  => 'payment.timeout.initiated'
    ];

    const PAYMENT_CRON_TIMEOUT_FAILED               = [
        'group' => 'timeout',
        'name'  => 'payment.timeout.failed'
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

    const PAYMENT_ELIGIBLE_FOR_AUTO_CAPTURE             = [
        'group' => 'capture',
        'name'  => 'payment.auto.capture.eligible'
    ];

    const PAYMENT_NOT_ELIGIBLE_FOR_AUTO_CAPTURE             = [
        'group' => 'capture',
        'name'  => 'payment.auto.capture.not.eligible'
    ];

    const PAYMENT_CAPTURE_VALIDATION_INITIATED             = [
        'group' => 'capture',
        'name'  => 'payment.capture.validation.initiated'
    ];

    const PAYMENT_CAPTURE_VALIDATION_SUCCESS             = [
        'group' => 'capture',
        'name'  => 'payment.capture.validation.success'
    ];

    const PAYMENT_CAPTURE_VALIDATION_FAILED             = [
        'group' => 'capture',
        'name'  => 'payment.capture.validation.failed'
    ];

    const PAYMENT_CAPTURE_GATEWAY_INITIATED = [
        'group' => 'capture',
        'name'  => 'payment.capture.gateway.initiated'
    ];

    const PAYMENT_CAPTURE_GATEWAY_SUCCESS            = [
        'group' => 'capture',
        'name'  => 'payment.capture.gateway.success'
    ];

    const PAYMENT_CAPTURE_GATEWAY_FAILED            = [
        'group' => 'capture',
        'name'  => 'payment.capture.gateway.failed'
    ];

    const PAYMENT_CAPTURE_QUEUE                     = [
        'group' => 'capture',
        'name'  => 'payment.capture.queue'
    ];

    const PAYMENT_CREATE_REQUEST_PROCESSED              = [
        'group' => 'payment_create_response',
        'name'  => 'payment.create.request.processed'
    ];

    const REARCH_PAYMENT_CREATE_REQUEST_PROCESSED       = [
        'group' => 'payment_create_response',
        'name'  => 'rearch.payment.create.request.processed'
    ];

    const PAYMENT_RESPONSE_SENT                         = [
        'group' => 'payment_response',
        'name'  => 'payment.response.sent'
    ];

    const PAYMENT_AUTO_REFUND_DATE_SET                     = [
        'group' => 'auto_refund',
        'name'  => 'payment.auto.refund.date'
    ];

    const PAYMENT_AUTO_REFUND_DATE_OVERRIDDEN                     = [
        'group' => 'auto_refund',
        'name'  => 'payment.auto.refund.overridden'
    ];

    const PAYMENT_AUTO_REFUND_ELIGIBLE                     = [
        'group' => 'auto_refund',
        'name'  => 'payment.auto.refund.eligible'
    ];

    const PAYMENT_AUTO_REFUND_INITIATED                     = [
        'group' => 'auto_refund',
        'name'  => 'payment.auto.refund.initiated'
    ];

    const PAYMENT_AUTO_REFUND_SUCCESS                     = [
        'group' => 'auto_refund',
        'name'  => 'payment.auto.refund.success'
    ];

    const PAYMENT_AUTO_REFUND_FAILED                     = [
        'group' => 'auto_refund',
        'name'  => 'payment.auto.refund.failed'
    ];

    // payment verification
    const PAYMENT_VERIFICATION_PAYMENT_BLOCKED          = [
        'group' => 'verification',
        'name'  => 'payment.verification.payment.blocked'
    ];

    const PAYMENT_VERIFICATION_INITIATED                = [
        'group' => 'verification',
        'name'  => 'payment.verification.initiated'
    ];

    const PAYMENT_VERIFICATION_STATUS_NOT_FOR_VERIFY    = [
        'group' => 'verification',
        'name'  => 'payment.verification.status.not.for.verify'
    ];

    const PAYMENT_VERIFICATION_SCHEDULER_VERIFY_INITIATED = [
        'group' => 'verification',
        'name'  => 'payment.verification.scheduler.verify.initiated'
    ];

    const PAYMENT_VERIFICATION_FILTERED_FINAL_FAILURE   = [
        'group' => 'verification',
        'name'  => 'payment.verification.filtered.final.failure'
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

    const SETTLEMENT_CREATION_SKIPPED = [
        'group' => 'skipped',
        'name'  => 'settlement.creation.skipped'
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
        'name'  => 'fta.creation.success'
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

    const BEAM_FILE_PUSH_REQUEST_SUCCESS = [
        'group' => 'success',
        'name'  => 'beam.push.request.success'
    ];

    const BEAM_FILE_PUSH_REQUEST_RETRY = [
        'group' => 'retry',
        'name'  => 'beam.push.request.retry'
    ];

    const BEAM_FILE_PUSH_REQUEST_FAILED = [
        'group' => 'failure',
        'name'  => 'beam.push.request.failed'
    ];

    const FTA_UTR_UPDATED = [
        'group' => 'update',
        'name'  => 'fta.utr.updated'
    ];

    const FTA_DATA_UPDATED_FROM_REVERSE_FEED = [
        'group' => 'update',
        'name'  => 'fta.data.updated.reverse.feed'
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

    // onboarding events
    const CAPTCHA_TOKEN_VERIFICATION_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'captcha.token_verification.success'
    ];

    const SIGNUP_CAPTCHA_VERIFICATION_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.captcha_verification.success',
    ];

    const SIGNUP_CAPTCH_VERIFICATION_FAILED = [
        'group' => 'onboarding',
        'name'  => 'signup.captcha_verification.failed',
    ];

    const SIGNUP_EMAIL_VERIFICATION_FAILED = [
        'group' => 'onboarding',
        'name'  => 'signup.email_verification.failed',
    ];

    const SIGNUP_EMAIL_VERIFICATION_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.email_verification.success',
    ];

    const SIGNUP_RESEND_VERIFICATION_EMAIL_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.resend_verification_email.success',
    ];

    const SIGNUP_RESEND_VERIFICATION_EMAIL_OTP_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.resend_verification_email_otp.success',
    ];

    const SIGNUP_SEND_VERIFICATION_EMAIL_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.send_verification_email.success',
    ];

    const SIGNUP_SEND_VERIFICATION_EMAIL_OTP_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.send_verification_email_otp.success',
    ];

    const SIGNUP_APPLY_COUPON_CODE_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.apply_coupon_code.success',
    ];

    const SIGNUP_APPLY_COUPON_CODE_FAILED = [
        'group' => 'onboarding',
        'name'  => 'signup.apply_coupon_code.failed',
    ];

    const APPLY_COUPON_CODE_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'mtu.apply_coupon_code.success',
    ];

    const APPLY_COUPON_CODE_FAILED = [
        'group' => 'onboarding',
        'name'  => 'mtu.apply_coupon_code.failed',
    ];
    const M2M_APPLY_COUPON_CODE_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'm2m.apply_coupon_code.success',
    ];

    const M2M_APPLY_COUPON_CODE_FAILED = [
        'group' => 'onboarding',
        'name'  => 'm2m.apply_coupon_code.failed',
    ];
    const SIGNUP_FINISH_SIGNUP_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.finish_signup.success',
    ];

    const SIGNUP_FINISH_SIGNUP_FAILED = [
        'group' => 'onboarding',
        'name'  => 'signup.finish_signup.failed',
    ];

    const SIGNUP_CREATE_ACCOUNT_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'signup.create_account.success',
    ];

    const SIGNUP_CREATE_ACCOUNT_SUCCESS_WITH_GOOGLE = [
        'group' => 'onboarding',
        'name'  => 'signup.create_account.success.with.google',
    ];

    const LOGIN_SUCCESS_WITH_GOOGLE = [
        'group' => 'onboarding',
        'name'  => 'login.success.with.google',
    ];

    const SIGNUP_CREATE_ACCOUNT_FAILED = [
        'group' => 'onboarding',
        'name'  => 'signup.create_account.failed',
    ];

    const PRODUCT_SWITCH = [
        'group' => 'onboarding',
        'name' => 'product_switch',
    ];

    const ACT_SUBMIT_FORM_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'act.submit_form.success',
    ];

    const ACT_SUBMIT_FORM_FAILED = [
        'group' => 'onboarding',
        'name'  => 'act.submit_form.failed',
    ];

    const ACT_CHANGE_ACTIVATION_STATUS_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'act.change_activation_status.success',
    ];

    const ACT_CHANGE_ACTIVATION_FLOW_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'act.change_activation_flow.success',
    ];

    const KYC_FORM_SUBMIT_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'kyc.form_submit.success',
    ];

    const KYC_SAVE_MODIFICATIONS_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'kyc.save_modifications.success',
    ];

    const KYC_VERIFIER_SERVICE_RESPONSE_TIME = [
        'group' => 'onboarding',
        'name' => 'kyc.verifier_service.response_time',
    ];

    const KYC_PERSONAL_PAN_VERIFICATION = [
        'group' => 'onboarding',
        'name'  => 'kyc.auto_poi.personal.verification',
    ];

    const KYC_CIN_VERIFICATION = [
        'group' => 'onboarding',
        'name'  => 'kyc.auto_cin.verification',
    ];

    const KYC_COMPANY_PAN_VERIFICATION = [
        'group' => 'onboarding',
        'name'  => 'kyc.auto_poi.business.verification',
    ];

    const KYC_GSTIN_VERIFICATION = [
        'group' => 'onboarding',
        'name'  => 'kyc.auto_gstin.verification',
    ];

    const KYC_POA_VERIFICATION = [
        'group' => 'onboarding',
        'name'  => 'kyc.auto_poa.business.verification',
    ];

    const KYC_PENNY_TESTING_SUCCESS_RATE = [
        'group' => 'onboarding',
        'name'  => 'kyc.penny_testing.success.rate',
    ];

    const KYC_UPLOAD_DOCUMENT_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'kyc.upload_document.success',
    ];

    const DOCUMENT_VERIFICATION_OCR = [
        'group' => 'onboarding',
        'name'  => 'document.verification.ocr',
    ];

    const KYC_UPLOAD_DOCUMENT_FAILED = [
        'group' => 'onboarding',
        'name'  => 'kyc.upload_document.failed',
    ];

    const KYC_SAVE_MODIFICATIONS_FAILED = [
        'group' => 'onboarding',
        'name'  => 'kyc.save_modifications.failed',
    ];

    const MERCHANT_DEDUPE = [
        'group' => 'onboarding',
        'name'  => 'merchant.dedupe'
    ];

    const MERCHANT_REFERRAL_CREDITS = [
        'group' => 'onboarding',
        'name'  => 'merchant.referral_credits'
    ];

    const MERCHANT_REFERRAL = [
        'group' => 'onboarding',
        'name'  => 'merchant.referral'
    ];

    const MERCHANT_AUTO_NC = [
        'group' => 'onboarding',
        'name'  => 'merchant.auto_nc'
    ];

    const PARTNER_AUTO_NC = [
        'group' => 'onboarding',
        'name'  => 'partner.auto_nc'
    ];

    const PAYMENT_PAGE_CREATED = [
        'group' => 'payment_page',
        'name'  => 'payment_page.created',
    ];

    // Onboarding Attribute Events
    const MERCHANT_ONBOARDING_CATEGORY_SET = [
        'group' => 'onboarding',
        'name'  => 'merchant_onboarding_category.set'
    ];

    const MERCHANT_ONBOARDING_RESET_PASSWORD_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'merchant_onboarding.reset_password.success'
    ];

    const MERCHANT_ONBOARDING_RESET_PASSWORD_FAILURE = [
        'group' => 'onboarding',
        'name'  => 'merchant_onboarding.reset_password.failure'
    ];

    const MERCHANT_ONBOARDING_LOGIN_FAILURE = [
        'group' => 'onboarding',
        'name'  => 'merchant_onboarding.login.failure'
    ];

    const MERCHANT_ONBOARDING_LOGIN_SUCCESS = [
        'group' => 'onboarding',
        'name'  => 'merchant_onboarding.login.success'
    ];

    const LOGIN_SUCCESS_WITH_MOBILE = [
        'group' => 'onboarding',
        'name'  => 'login.success.with.mobile'
    ];
    const PARTNERSHIP_PARTNER_SIGNUP = [
        'group' => 'onboarding',
        'name'  => 'partnerships.partner.signup'
    ];

    const PARTNERSHIP_SUBMERCHANT_SIGNUP = [
        'group' => 'onboarding',
        'name'  => 'partnerships.submerchant.signup'
    ];

    const PARTNERSHIP_SUBMERCHANT_SIGNUP_ERROR = [
        'group' => 'onboarding',
        'name'  => 'partnerships.submerchant.signup.error'
    ];

    const MERCHANT_ONBOARDING_CATEGORY_UPDATE = [
        'group' => 'onboarding',
        'name'  => 'merchant_onboarding_category.update'
    ];

    const VIRTUAL_ACCOUNT_CREATED = [
        'group' => 'virtual_account',
        'name'  => 'virtual_account.created',
    ];

    const VIRTUAL_ACCOUNT_CLOSED = [
        'group' => 'virtual_account',
        'name'  => 'virtual_account.closed',
    ];

    const QR_CODE_CLOSED = [
        'group' => 'qr_code',
        'name'  => 'qr_code.closed',
    ];

    const QR_CODE_CREATED = [
        'group' => 'qr_code',
        'name'  => 'qr_code.created',
    ];

    const QR_CODE_CREDITED = [
        'group' => 'qr_code',
        'name'  => 'qr_code.credited',
    ];

    const BANK_TRANSFER_REQUEST = [
        'group' => 'bank_transfer',
        'name'  => 'bank_transfer.request',
    ];

    const UPI_TRANSFER_REQUEST = [
        'group' => 'upi_transfer',
        'name'  => 'upi_transfer.request',
    ];

    const BANK_TRANSFER_UNEXPECTED_PAYMENT = [
        'group' => 'bank_transfer',
        'name'  => 'bank_transfer.unexpected_payment',
    ];

    const UPI_TRANSFER_UNEXPECTED_PAYMENT = [
        'group' => 'upi_transfer',
        'name'  => 'upi_transfer.unexpected_payment',
    ];

    const VIRTUAL_VPA_PREFIX_VALIDATE = [
        'group' => 'virtual_vpa_prefix',
        'name'  => 'virtual_vpa_prefix.validate',
    ];

    // payment config events
    const PAYMENT_CONFIG_CREATION_INITIATED                      = [
        'group' => 'initiation',
        'name'  => 'payment_config.creation.initiated'
    ];

    const PAYMENT_CONFIG_UPDATION_INITIATED                      = [
        'group' => 'initiation',
        'name'  => 'payment_config.updation.initiated'
    ];

    const EMAIL_ATTEMPTED = [
        'group' => 'email',
        'name'  => 'email.attempted',
    ];

    const EMAIL_SUCCESS = [
        'group' => 'email',
        'name'  => 'email.success',
    ];

    const EMAIL_ATTEMPT_FAILED = [
        'group' => 'email',
        'name'  => 'email.attempt_failed',
    ];

    const MAILGUN_ATTEMPT_FAILED = [
        'group' => 'email',
        'name'  => 'email.mailgun_attempt_failed',
    ];

    const MAILGUN_ATTEMPT_SUCCESS = [
        'group' => 'email',
        'name'  => 'email.mailgun_attempt_success',
    ];

    const DISPUTE_CREATED = [
        'group' => 'dispute',
        'name'  => 'dispute.created'
    ];

    const DISPUTE_PROCESSED = [
        'group' => 'dispute',
        'name'  => 'dispute.processed'
    ];

    const BVS_CONSUMED_VALIDATION_DOCUMENT_VERIFICATION_RESULTS = [
        'group' => 'onboarding',
        'name'  => 'bvs.consumed_validation.document_verification.results'
    ];

    const EMAIL_REWARD_SENT = [
        'group' => 'email',
        'name'  => 'email.reward.sent',
    ];

    const PARTNERSHIPS_APPSTORE_WA_PL_CREATED = [
        'group' => 'onboarding',
        'name'  => 'partnerships.appstore.wa.pl.created'
    ];

    const PARTNERSHIPS_APPSTORE_WA_PL_WRONG_TEMPLATE = [
        'group' => 'onboarding',
        'name'  => 'partnerships.appstore.wa.pl.wrong_template'
    ];

    const PARTNERSHIPS_APPSTORE_WA_PL_FAILED = [
        'group' => 'onboarding',
        'name'  => 'partnerships.appstore.wa.pl.failed'
    ];

    const PAYMENT_KAFKA_PUSH_SUCCESS                  = [
        'group' => 'kafka_push',
        'name'  => 'payment.kafka.push.success'
    ];

    const PAYMENT_KAFKA_PUSH_FAILED                  = [
        'group' => 'kafka_push',
        'name'  => 'payment.kafka.push.failed'
    ];

    //M2M Reward Events
    const REWARD_UPDATED = [
        'group' => 'updation',
        'name'  => 'reward.updated'
    ];

    const REWARD_ICON = [
        'group' => 'email',
        'name'  => 'reward.email.click.icon'
    ];

    const REWARD_COUPON = [
        'group' => 'email',
        'name'  => 'reward.email.click.coupon'
    ];

    const REWARD_SMS_SENT = [
        'group' => 'sms',
        'name'  => 'reward.sms.sent'
    ];

    const REWARD_REDIRECT = [
        'group' => 'sms',
        'name'  => 'reward.redirect'
    ];

    const REWARD_COUPON_DISTRIBUTED = [
        'group' => 'reward_coupon',
        'name'  => 'reward.coupon_distributed'
    ];

    const REWARD_COUPON_COUNT_THRESHOLD = [
        'group' => 'reward_coupon',
        'name'  => 'reward.coupon_count_threshold'
    ];

    const TRUSTED_BADGE_CRON_INITIATED = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.cron.initiated'
    ];

    const TRUSTED_BADGE_CRON_RESULT = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.cron.result'
    ];

    const TRUSTED_BADGE_CRON_FAILURE = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.cron.failure'
    ];

    public const TRUSTED_BADGE_MAIL_CTA = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.email.cta'
    ];

    public const TRUSTED_BADGE_WELCOME_MAIL_INITIATED = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.email.welcome.initiated',
    ];

    public const TRUSTED_BADGE_OPTOUT_NOTIFY_MAIL_INITIATED = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.email.optout_notify.initiated',
    ];

    public const TRUSTED_BADGE_OPTIN_REQUEST_MAIL_INITIATED = [
        'group' => 'trusted_badge',
        'name'  => 'trusted_badge.email.optin_request.initiated',
    ];

    const PAYMENT_ELIGIBILITY_CHECK_INITIATED = [
        'group' => 'eligibility',
        'name'  => 'payment.eligibility_check.initiated'
    ];

    const PAYMENT_ELIGIBILITY_CHECK_PROCESSED = [
        'group' => 'eligibility',
        'name'  => 'payment.eligibility_check.processed'
    ];

    const BIN_API_SUCCESS = [
        'group' => 'bin_api',
        'name'  => 'bin.api.success'
    ];

    const BIN_API_FAILURE = [
        'group' => 'bin_api',
        'name'  => 'bin.api.failure'
    ];

    const BIN_API_INITIATION = [
        'group' => 'bin_api',
        'name'  => 'bin.api.initiation'
    ];

    const ERROR_RESPONSE              = [
        'group' => 'error_response',
        'name'  => 'error.response'
    ];

    const PAYMENT_NBPLUS_CALL_INITIATED = [
      'group'   => 'nbplus',
      'name'    => 'payment.nbplus.request.initiated'
    ];

    const PAYMENT_NBPLUS_CALL_PROCESSED = [
        'group'   => 'nbplus',
        'name'    => 'payment.nbplus.request.processed'
    ];
}
