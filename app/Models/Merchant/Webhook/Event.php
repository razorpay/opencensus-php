<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Product;
use RZP\Models\FundAccount;

/**
 * The events whether they are enabled or disabled are store in bit format.
 * See this link for a guide on bitwise operations:
 * http://stackoverflow.com/questions/47981/how-do-you-set-clear-and-toggle-a-single-bit-in-c-c
 */
class Event
{
    const PAYMENT_AUTHORIZED                = 'payment.authorized';
    const PAYMENT_PENDING                   = 'payment.pending';
    const PAYMENT_FAILED                    = 'payment.failed';
    const PAYMENT_CAPTURED                  = 'payment.captured';
    const PAYMENT_DISPUTE_CREATED           = 'payment.dispute.created';
    const ORDER_PAID                        = 'order.paid';
    const INVOICE_PAID                      = 'invoice.paid';
    const INVOICE_PARTIALLY_PAID            = 'invoice.partially_paid';
    const INVOICE_EXPIRED                   = 'invoice.expired';
    const VPA_EDITED                        = 'vpa.edited';
    const P2P_CREATED                       = 'p2p.created';
    const P2P_REJECTED                      = 'p2p.rejected';
    const P2P_TRANSFERRED                   = 'p2p.transferred';
    const SUBSCRIPTION_AUTHENTICATED        = 'subscription.authenticated';
    const SUBSCRIPTION_ACTIVATED            = 'subscription.activated';
    const SUBSCRIPTION_CHARGED              = 'subscription.charged';
    const SUBSCRIPTION_PENDING              = 'subscription.pending';
    const SUBSCRIPTION_HALTED               = 'subscription.halted';
    const SUBSCRIPTION_CANCELLED            = 'subscription.cancelled';
    const SUBSCRIPTION_COMPLETED            = 'subscription.completed';
    const SUBSCRIPTION_UPDATED              = 'subscription.updated';
    const SUBSCRIPTION_PAUSED               = 'subscription.paused';
    const SUBSCRIPTION_RESUMED              = 'subscription.resumed';
    const TOKEN_CONFIRMED                   = 'token.confirmed';
    const TOKEN_REJECTED                    = 'token.rejected';
    const TOKEN_PAUSED                      = 'token.paused';
    const TOKEN_CANCELLED                   = 'token.cancelled';
    const SETTLEMENT_PROCESSED              = 'settlement.processed';
    const VIRTUAL_ACCOUNT_CREDITED          = 'virtual_account.credited';
    const VIRTUAL_ACCOUNT_CREATED           = 'virtual_account.created';
    const VIRTUAL_ACCOUNT_CLOSED            = 'virtual_account.closed';
    const QR_CODE_CLOSED                    = 'qr_code.closed';
    const QR_CODE_CREATED                   = 'qr_code.created';
    const QR_CODE_CREDITED                  = 'qr_code.credited';
    const PAYMENT_DISPUTE_WON               = 'payment.dispute.won';
    const PAYMENT_DISPUTE_LOST              = 'payment.dispute.lost';
    const PAYMENT_DISPUTE_CLOSED            = 'payment.dispute.closed';
    const PAYMENT_DISPUTE_UNDER_REVIEW      = 'payment.dispute.under_review';
    const PAYMENT_DISPUTE_ACTION_REQUIRED   = 'payment.dispute.action_required';
    const TRANSACTION_CREATED               = 'transaction.created';
    const PAYOUT_PROCESSED                  = 'payout.processed';
    const PAYOUT_REVERSED                   = 'payout.reversed';
    const PAYOUT_FAILED                     = 'payout.failed';
    const FUND_ACCOUNT_VALIDATION_COMPLETED = 'fund_account.validation.completed';
    const FUND_ACCOUNT_VALIDATION_FAILED    = 'fund_account.validation.failed';
    const PAYMENT_DOWNTIME_STARTED          = 'payment.downtime.started';
    const PAYMENT_DOWNTIME_UPDATED          = 'payment.downtime.updated';
    const PAYMENT_DOWNTIME_RESOLVED         = 'payment.downtime.resolved';
    const PAYOUT_QUEUED                     = 'payout.queued';
    const PAYOUT_INITIATED                  = 'payout.initiated';
    const PAYOUT_UPDATED                    = 'payout.updated';
    const PAYOUT_REJECTED                   = 'payout.rejected';
    const PAYOUT_PENDING                    = 'payout.pending';
    const REFUND_SPEED_CHANGED              = 'refund.speed_changed';
    const REFUND_PROCESSED                  = 'refund.processed';
    const REFUND_FAILED                     = 'refund.failed';
    const TRANSACTION_UPDATED               = 'transaction.updated';
    const REFUND_CREATED                    = 'refund.created';
    const REFUND_ARN_UPDATED                = 'refund.arn_updated';
    const TRANSFER_PROCESSED                = 'transfer.processed';
    const TRANSFER_SETTLED                  = 'transfer.settled';
    const TRANSFER_FAILED                   = 'transfer.failed';
    const TERMINAL_CREATED                  = 'terminal.created';
    const TERMINAL_ACTIVATED                = 'terminal.activated';
    const TERMINAL_FAILED                   = 'terminal.failed'; // Terminal Creation failed on gateway
    const ACCOUNT_SUSPENDED                 = 'account.suspended';
    const ACCOUNT_FUNDS_HOLD                = 'account.funds_hold';
    const ACCOUNT_FUNDS_UNHOLD              = 'account.funds_unhold';
    const ACCOUNT_INTERNATIONAL_ENABLED     = 'account.international_enabled';
    const ACCOUNT_INTERNATIONAL_DISABLED    = 'account.international_disabled';
    const ACCOUNT_INSTANTLY_ACTIVATED       = 'account.instantly_activated';
    const ACCOUNT_ACTIVATED_KYC_PENDING     = 'account.activated_kyc_pending';
    const ACCOUNT_UNDER_REVIEW              = 'account.under_review';
    const ACCOUNT_NEEDS_CLARIFICATION       = 'account.needs_clarification';
    const ACCOUNT_ACTIVATED                 = 'account.activated';
    const ACCOUNT_REJECTED                  = 'account.rejected';
    const ACCOUNT_UPDATED                   = 'account.updated';
    const ACCOUNT_PAYMENTS_ENABLED          = 'account.payments_enabled';
    const ACCOUNT_PAYMENTS_DISABLED         = 'account.payments_disabled';
    const ACCOUNT_MAPPED_TO_PARTNER         = 'account.mapped_to_partner';
    const ACCOUNT_APP_AUTHORIZATION_REVOKED = 'account.app.authorization_revoked';
    const PAYOUT_LINK_ISSUED                = 'payout_link.issued';
    const PAYOUT_LINK_PROCESSING            = 'payout_link.processing';
    const PAYOUT_LINK_ATTEMPTED             = 'payout_link.attempted';
    const PAYOUT_LINK_CANCELLED             = 'payout_link.cancelled';
    const PAYOUT_LINK_PROCESSED             = 'payout_link.processed';
    const PAYOUT_LINK_EXPIRED               = 'payout_link.expired';
    const PAYOUT_LINK_PENDING               = 'payout_link.pending';
    const PAYOUT_LINK_REJECTED              = 'payout_link.rejected';
    const PAYMENT_CREATED                   = 'payment.created';
    const PAYMENT_LINK_PAID                 = 'payment_link.paid';
    const PAYMENT_LINK_PARTIALLY_PAID       = 'payment_link.partially_paid';
    const PAYMENT_LINK_EXPIRED              = 'payment_link.expired';
    const PAYMENT_LINK_CANCELLED            = 'payment_link.cancelled';
    const BANKING_ACCOUNTS_ISSUED           = 'banking_accounts.issued';
    const PAYMENT_PAGE_PAID                 = 'payment_page.paid';
    const P2P_TRANSACTION_CREATED           = 'customer.transaction.created';
    const P2P_TRANSACTION_COMPLETED         = 'customer.transaction.completed';
    const P2P_TRANSACTION_FAILED            = 'customer.transaction.failed';
    const P2P_VPA_CREATED                   = 'customer.vpa.created';
    const P2P_VPA_DELETED                   = 'customer.vpa.deleted';
    const P2P_VERIFICATION_COMPLETED        = 'customer.verification.completed';
    const P2P_DEREGISTRATION_COMPLETED      = 'customer.deregistration.completed';
    const PAYOUT_DOWNTIME_STARTED           = 'payout.downtime.started';
    const PAYOUT_DOWNTIME_RESOLVED          = 'payout.downtime.resolved';
    const ZAPIER_PAYMENT_PAGE_PAID_V1       = 'zapier.payment_page.paid.v1';
    const SHIPROCKET_PAYMENT_PAGE_PAID_V1   = 'shiprocket.payment_page.paid.v1';

    // Payouts Batch API
    const PAYOUT_CREATION_FAILED = 'payout.creation.failed';

    //V2 partner Onboarding events
    const PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW        = 'product.payment_gateway.under_review';
    const PAYMENT_GATEWAY_PRODUCT_ACTIVATED           = 'product.payment_gateway.activated';
    const PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION = 'product.payment_gateway.needs_clarification';
    const PAYMENT_GATEWAY_PRODUCT_REJECTED            = 'product.payment_gateway.rejected';
    const PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED = 'product.payment_gateway.instantly_activated';
    const PAYMENT_LINKS_PRODUCT_UNDER_REVIEW          = 'product.payment_links.under_review';
    const PAYMENT_LINKS_PRODUCT_ACTIVATED             = 'product.payment_links.activated';
    const PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION   = 'product.payment_links.needs_clarification';
    const PAYMENT_LINKS_PRODUCT_REJECTED              = 'product.payment_links.rejected';
    const PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED   = 'product.payment_links.instantly_activated';
    const PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING = 'product.payment_gateway.activated_kyc_pending';
    const PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING   = 'product.route.activated_kyc_pending';
    const ROUTE_PRODUCT_UNDER_REVIEW                    = 'product.route.under_review';
    const ROUTE_PRODUCT_ACTIVATED                       = 'product.route.activated';
    const ROUTE_PRODUCT_NEEDS_CLARIFICATION             = 'product.route.needs_clarification';
    const ROUTE_PRODUCT_REJECTED                        = 'product.route.rejected';

    const NO_DOC_ONBOARDING_GMV_LIMIT_WARNING           = 'account.no_doc_onboarding_gmv_limit_warning';
    const INSTANT_ACTIVATION_GMV_LIMIT_WARNING          = 'account.instant_activation_gmv_limit_warning';

    //toeknisation events
    const TOKEN_SERVICE_PROVIDER_ACTIVATED                   = 'token.service_provider.activated';
    const TOKEN_SERVICE_PROVIDER_SUSPENDED                   = 'token.service_provider.cancelled';
    const TOKEN_SERVICE_PROVIDER_DEACTIVATED                 = 'token.service_provider.deactivated';
    const TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED              = 'token.service_provider.deactivated';

    //Issuing - Wallet Events
    const ISSUING_LOAD_CREATED                          = 'load.created';
    const ISSUING_LOAD_SUCCESS                          = 'load.success';
    const ISSUING_LOAD_FAILED                           = 'load.failed';
    const ISSUING_WITHDRAWAL_CREATED                    = 'withdrawal.created';
    const ISSUING_WITHDRAWAL_INITIATED                  = 'withdrawal.initiated';
    const ISSUING_WITHDRAWAL_PROCESSED                  = 'withdrawal.processed';
    const ISSUING_WITHDRAWAL_FAILED                     = 'withdrawal.failed';
    const ISSUING_KYC_SUCCESS                           = 'kyc.success';
    const ISSUING_KYC_MANUAL_REVIEW                     = 'kyc.manual_review';
    const ISSUING_KYC_MANUALLY_VERIFIED                 = 'kyc.manually_verified';
    const ISSUING_KYC_FAILED                            = 'kyc.failed';
    const ISSUING_KYC_IN_PROGRESS                       = 'kyc.in_progress';
    const ISSUING_BENEFICIARY_CREATED                   = 'beneficiary.created';
    const ISSUING_BENEFICIARY_ACTIVE                    = 'beneficiary.active';
    const ISSUING_BENEFICIARY_MANUAL_REVIEW             = 'beneficiary.manual_review';
    const ISSUING_BENEFICIARY_FAILED                    = 'beneficiary.failed';
    const ISSUING_TRANSACTION_CREATED                   = 'issuing.transaction.created';

    protected static $events = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_FAILED,
        self::PAYMENT_PENDING,
        self::PAYMENT_CAPTURED,
        self::PAYMENT_DISPUTE_CREATED,
        self::ORDER_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_AUTHENTICATED,
        self::SUBSCRIPTION_PAUSED,
        self::SUBSCRIPTION_RESUMED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        self::SUBSCRIPTION_UPDATED,
        self::TOKEN_CONFIRMED,
        self::TOKEN_REJECTED,
        self::TOKEN_PAUSED,
        self::TOKEN_CANCELLED,
        self::SETTLEMENT_PROCESSED,
        self::VIRTUAL_ACCOUNT_CREDITED,
        self::VIRTUAL_ACCOUNT_CREATED,
        self::VIRTUAL_ACCOUNT_CLOSED,
        self::QR_CODE_CLOSED,
        self::QR_CODE_CREATED,
        self::QR_CODE_CREDITED,
        self::PAYMENT_DISPUTE_WON,
        self::PAYMENT_DISPUTE_LOST,
        self::PAYMENT_DISPUTE_CLOSED,
        self::PAYMENT_DISPUTE_UNDER_REVIEW,
        self::PAYMENT_DISPUTE_ACTION_REQUIRED,
        self::TRANSACTION_CREATED,
        self::PAYOUT_PROCESSED,
        self::PAYOUT_REVERSED,
        self::PAYOUT_FAILED,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED,
        self::FUND_ACCOUNT_VALIDATION_FAILED,
        self::PAYMENT_DOWNTIME_STARTED,
        self::PAYMENT_DOWNTIME_UPDATED,
        self::PAYMENT_DOWNTIME_RESOLVED,
        self::PAYOUT_QUEUED,
        self::PAYOUT_INITIATED,
        self::PAYOUT_UPDATED,
        self::PAYOUT_REJECTED,
        self::PAYOUT_PENDING,
        self::REFUND_SPEED_CHANGED,
        self::REFUND_PROCESSED,
        self::REFUND_FAILED,
        self::TRANSACTION_UPDATED,
        self::REFUND_CREATED,
        self::REFUND_ARN_UPDATED,
        self::TRANSFER_PROCESSED,
        self::TRANSFER_SETTLED,
        self::TRANSFER_FAILED,
        self::TERMINAL_CREATED,
        self::TERMINAL_ACTIVATED,
        self::TERMINAL_FAILED,
        self::ACCOUNT_SUSPENDED,
        self::ACCOUNT_FUNDS_HOLD,
        self::ACCOUNT_FUNDS_UNHOLD,
        self::ACCOUNT_INTERNATIONAL_ENABLED,
        self::ACCOUNT_INTERNATIONAL_DISABLED,
        self::ACCOUNT_INSTANTLY_ACTIVATED,
        self::ACCOUNT_ACTIVATED_KYC_PENDING,
        self::ACCOUNT_UNDER_REVIEW,
        self::ACCOUNT_NEEDS_CLARIFICATION,
        self::ACCOUNT_ACTIVATED,
        self::ACCOUNT_REJECTED,
        self::ACCOUNT_UPDATED,
        self::ACCOUNT_PAYMENTS_ENABLED,
        self::ACCOUNT_PAYMENTS_DISABLED,
        self::ACCOUNT_MAPPED_TO_PARTNER,
        self::ACCOUNT_APP_AUTHORIZATION_REVOKED,
        self::PAYMENT_CREATED,
        self::PAYOUT_LINK_ISSUED,
        self::PAYOUT_LINK_PROCESSING,
        self::PAYOUT_LINK_ATTEMPTED,
        self::PAYOUT_LINK_CANCELLED,
        self::PAYOUT_LINK_PROCESSED,
        self::PAYOUT_LINK_EXPIRED,
        self::PAYOUT_LINK_PENDING,
        self::PAYOUT_LINK_REJECTED,
        self::PAYMENT_LINK_PAID,
        self::PAYMENT_LINK_PARTIALLY_PAID,
        self::PAYMENT_LINK_EXPIRED,
        self::PAYMENT_LINK_CANCELLED,
        self::BANKING_ACCOUNTS_ISSUED,
        self::PAYMENT_PAGE_PAID,
        self::P2P_TRANSACTION_CREATED,
        self::P2P_TRANSACTION_COMPLETED,
        self::P2P_TRANSACTION_FAILED,
        self::P2P_VPA_CREATED,
        self::P2P_VPA_DELETED,
        self::P2P_VERIFICATION_COMPLETED,
        self::P2P_DEREGISTRATION_COMPLETED,
        self::PAYOUT_DOWNTIME_STARTED,
        self::PAYOUT_DOWNTIME_RESOLVED,
        self::PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW,
        self::PAYMENT_GATEWAY_PRODUCT_REJECTED,
        self::PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED,
        self::PAYOUT_CREATION_FAILED,
        self::PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED,
        self::PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING,
        self::PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION,
        self::PAYMENT_LINKS_PRODUCT_REJECTED,
        self::PAYMENT_LINKS_PRODUCT_UNDER_REVIEW,
        self::NO_DOC_ONBOARDING_GMV_LIMIT_WARNING,
        self::ZAPIER_PAYMENT_PAGE_PAID_V1,
        self::SHIPROCKET_PAYMENT_PAGE_PAID_V1,
        self::TOKEN_SERVICE_PROVIDER_ACTIVATED,
        self::TOKEN_SERVICE_PROVIDER_SUSPENDED,
        self::TOKEN_SERVICE_PROVIDER_DEACTIVATED,
        self::TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED,
        self::INSTANT_ACTIVATION_GMV_LIMIT_WARNING,
        self::ROUTE_PRODUCT_UNDER_REVIEW,
        self::ROUTE_PRODUCT_ACTIVATED,
        self::ROUTE_PRODUCT_NEEDS_CLARIFICATION,
        self::ROUTE_PRODUCT_REJECTED,
        self::ISSUING_LOAD_CREATED,
        self::ISSUING_LOAD_SUCCESS,
        self::ISSUING_LOAD_FAILED,
        self::ISSUING_WITHDRAWAL_CREATED,
        self::ISSUING_WITHDRAWAL_INITIATED,
        self::ISSUING_WITHDRAWAL_PROCESSED,
        self::ISSUING_WITHDRAWAL_FAILED,
        self::ISSUING_KYC_SUCCESS,
        self::ISSUING_KYC_MANUAL_REVIEW,
        self::ISSUING_KYC_MANUALLY_VERIFIED,
        self::ISSUING_KYC_FAILED,
        self::ISSUING_KYC_IN_PROGRESS,
        self::ISSUING_BENEFICIARY_CREATED,
        self::ISSUING_BENEFICIARY_ACTIVE,
        self::ISSUING_BENEFICIARY_MANUAL_REVIEW,
        self::ISSUING_BENEFICIARY_FAILED,
        self::ISSUING_TRANSACTION_CREATED,
    ];

    /**
     * Events which are present in the system and
     * can be enabled/disabled.
     * @var array
     */
    protected static $names = [
        self::PAYMENT_AUTHORIZED,
        self::PAYMENT_PENDING,
        self::PAYMENT_FAILED,
        self::PAYMENT_CAPTURED,
        self::PAYMENT_DISPUTE_CREATED,
        self::ORDER_PAID,
        self::INVOICE_PARTIALLY_PAID,
        self::INVOICE_PAID,
        self::INVOICE_EXPIRED,
        self::VPA_EDITED,
        self::P2P_CREATED,
        self::P2P_REJECTED,
        self::P2P_TRANSFERRED,
        self::SUBSCRIPTION_AUTHENTICATED,
        self::SUBSCRIPTION_PAUSED,
        self::SUBSCRIPTION_RESUMED,
        self::SUBSCRIPTION_ACTIVATED,
        self::SUBSCRIPTION_PENDING,
        self::SUBSCRIPTION_HALTED,
        self::SUBSCRIPTION_CHARGED,
        self::SUBSCRIPTION_CANCELLED,
        self::SUBSCRIPTION_COMPLETED,
        self::SUBSCRIPTION_UPDATED,
        self::TOKEN_CONFIRMED,
        self::TOKEN_REJECTED,
        self::TOKEN_PAUSED,
        self::TOKEN_CANCELLED,
        self::SETTLEMENT_PROCESSED,
        self::VIRTUAL_ACCOUNT_CREDITED,
        self::VIRTUAL_ACCOUNT_CREATED,
        self::VIRTUAL_ACCOUNT_CLOSED,
        self::QR_CODE_CLOSED,
        self::QR_CODE_CREATED,
        self::QR_CODE_CREDITED,
        self::PAYMENT_DISPUTE_WON,
        self::PAYMENT_DISPUTE_LOST,
        self::PAYMENT_DISPUTE_CLOSED,
        self::PAYMENT_DISPUTE_UNDER_REVIEW,
        self::PAYMENT_DISPUTE_ACTION_REQUIRED,
        self::TRANSACTION_CREATED,
        self::PAYOUT_PROCESSED,
        self::PAYOUT_REVERSED,
        self::PAYOUT_FAILED,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED,
        self::FUND_ACCOUNT_VALIDATION_FAILED,
        self::PAYMENT_DOWNTIME_STARTED,
        self::PAYMENT_DOWNTIME_UPDATED,
        self::PAYMENT_DOWNTIME_RESOLVED,
        self::PAYOUT_QUEUED,
        self::PAYOUT_INITIATED,
        self::PAYOUT_UPDATED,
        self::PAYOUT_REJECTED,
        self::PAYOUT_PENDING,
        self::REFUND_SPEED_CHANGED,
        self::REFUND_PROCESSED,
        self::REFUND_FAILED,
        self::REFUND_ARN_UPDATED,
        self::TRANSACTION_UPDATED,
        self::REFUND_CREATED,
        self::TRANSFER_PROCESSED,
        self::TRANSFER_SETTLED,
        self::TRANSFER_FAILED,
        self::TERMINAL_CREATED,
        self::TERMINAL_ACTIVATED,
        self::TERMINAL_FAILED,
        self::ACCOUNT_SUSPENDED,
        self::ACCOUNT_FUNDS_HOLD,
        self::ACCOUNT_FUNDS_UNHOLD,
        self::ACCOUNT_INTERNATIONAL_ENABLED,
        self::ACCOUNT_INTERNATIONAL_DISABLED,
        self::ACCOUNT_INSTANTLY_ACTIVATED,
        self::ACCOUNT_ACTIVATED_KYC_PENDING,
        self::ACCOUNT_UNDER_REVIEW,
        self::ACCOUNT_NEEDS_CLARIFICATION,
        self::ACCOUNT_ACTIVATED,
        self::ACCOUNT_REJECTED,
        self::ACCOUNT_UPDATED,
        self::ACCOUNT_PAYMENTS_ENABLED,
        self::ACCOUNT_PAYMENTS_DISABLED,
        self::ACCOUNT_MAPPED_TO_PARTNER,
        self::ACCOUNT_APP_AUTHORIZATION_REVOKED,
        self::PAYOUT_LINK_ISSUED,
        self::PAYOUT_LINK_ISSUED,
        self::PAYOUT_LINK_PROCESSING,
        self::PAYOUT_LINK_ATTEMPTED,
        self::PAYOUT_LINK_CANCELLED,
        self::PAYOUT_LINK_PROCESSED,
        self::PAYOUT_LINK_EXPIRED,
        self::PAYOUT_LINK_PENDING,
        self::PAYOUT_LINK_REJECTED,
        self::PAYMENT_CREATED,
        self::PAYMENT_LINK_PAID,
        self::PAYMENT_LINK_PARTIALLY_PAID,
        self::PAYMENT_LINK_EXPIRED,
        self::PAYMENT_LINK_CANCELLED,
        self::BANKING_ACCOUNTS_ISSUED,
        self::PAYMENT_PAGE_PAID,
        self::P2P_TRANSACTION_CREATED,
        self::P2P_TRANSACTION_COMPLETED,
        self::P2P_TRANSACTION_FAILED,
        self::P2P_VPA_CREATED,
        self::P2P_VPA_DELETED,
        self::P2P_VERIFICATION_COMPLETED,
        self::P2P_DEREGISTRATION_COMPLETED,
        self::PAYOUT_DOWNTIME_STARTED,
        self::PAYOUT_DOWNTIME_RESOLVED,
        self::PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW,
        self::PAYMENT_GATEWAY_PRODUCT_REJECTED,
        self::PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED,
        self::PAYOUT_CREATION_FAILED,
        self::PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING,
        self::NO_DOC_ONBOARDING_GMV_LIMIT_WARNING,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED,
        self::PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING,
        self::PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION,
        self::PAYMENT_LINKS_PRODUCT_REJECTED,
        self::PAYMENT_LINKS_PRODUCT_UNDER_REVIEW,
        self::ZAPIER_PAYMENT_PAGE_PAID_V1,
        self::SHIPROCKET_PAYMENT_PAGE_PAID_V1,
        self::TOKEN_SERVICE_PROVIDER_ACTIVATED,
        self::TOKEN_SERVICE_PROVIDER_SUSPENDED,
        self::TOKEN_SERVICE_PROVIDER_DEACTIVATED,
        self::TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED,
        self::INSTANT_ACTIVATION_GMV_LIMIT_WARNING,
        self::ROUTE_PRODUCT_UNDER_REVIEW,
        self::ROUTE_PRODUCT_ACTIVATED,
        self::ROUTE_PRODUCT_NEEDS_CLARIFICATION,
        self::ROUTE_PRODUCT_REJECTED,
        self::ISSUING_LOAD_CREATED,
        self::ISSUING_LOAD_SUCCESS,
        self::ISSUING_LOAD_FAILED,
        self::ISSUING_WITHDRAWAL_CREATED,
        self::ISSUING_WITHDRAWAL_INITIATED,
        self::ISSUING_WITHDRAWAL_PROCESSED,
        self::ISSUING_WITHDRAWAL_FAILED,
        self::ISSUING_KYC_SUCCESS,
        self::ISSUING_KYC_MANUAL_REVIEW,
        self::ISSUING_KYC_MANUALLY_VERIFIED,
        self::ISSUING_KYC_FAILED,
        self::ISSUING_KYC_IN_PROGRESS,
        self::ISSUING_BENEFICIARY_CREATED,
        self::ISSUING_BENEFICIARY_ACTIVE,
        self::ISSUING_BENEFICIARY_MANUAL_REVIEW,
        self::ISSUING_BENEFICIARY_FAILED,
        self::ISSUING_TRANSACTION_CREATED,
    ];

    // We have exhausted all the below bits for webhook events, add in $bitPosition2 for any new events
    public static $bitPosition = [
        self::PAYMENT_AUTHORIZED                => 1,
        self::PAYMENT_FAILED                    => 2,
        self::PAYMENT_CAPTURED                  => 3,
        self::ORDER_PAID                        => 4,
        self::INVOICE_PAID                      => 5,
        self::VPA_EDITED                        => 6,
        self::P2P_CREATED                       => 7,
        self::P2P_REJECTED                      => 8,
        self::P2P_TRANSFERRED                   => 9,
        self::SUBSCRIPTION_ACTIVATED            => 10,
        self::SUBSCRIPTION_PENDING              => 11,
        self::SUBSCRIPTION_HALTED               => 12,
        self::SUBSCRIPTION_CHARGED              => 13,
        self::SUBSCRIPTION_CANCELLED            => 14,
        self::SUBSCRIPTION_COMPLETED            => 15,
        // self::SUBSCRIPTION_EXPIRED              => 16,
        self::INVOICE_EXPIRED                   => 17,
        self::INVOICE_PARTIALLY_PAID            => 18,
        self::TOKEN_CONFIRMED                   => 19,
        self::TOKEN_REJECTED                    => 20,
        self::SETTLEMENT_PROCESSED              => 21,
        self::PAYMENT_DISPUTE_CREATED           => 22,
        self::VIRTUAL_ACCOUNT_CREDITED          => 23,
        self::VIRTUAL_ACCOUNT_CREATED           => 24,
        self::PAYMENT_DISPUTE_WON               => 25,
        self::PAYMENT_DISPUTE_LOST              => 26,
        self::PAYMENT_DISPUTE_CLOSED            => 27,
        self::TRANSACTION_CREATED               => 28,
        self::PAYOUT_PROCESSED                  => 30,
        self::PAYOUT_REVERSED                   => 31,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => 32,
        self::PAYMENT_DOWNTIME_STARTED          => 33,
        self::PAYMENT_DOWNTIME_RESOLVED         => 34,
        self::PAYOUT_QUEUED                     => 35,
        self::PAYOUT_INITIATED                  => 36,
        self::SUBSCRIPTION_UPDATED              => 37,
        self::REFUND_SPEED_CHANGED              => 38,
        self::REFUND_PROCESSED                  => 39,
        self::REFUND_FAILED                     => 40,
        self::VIRTUAL_ACCOUNT_CLOSED            => 41,
        self::PAYOUT_FAILED                     => 42,
        self::REFUND_CREATED                    => 43,
        self::TERMINAL_ACTIVATED                => 44,
        self::TERMINAL_FAILED                   => 45,
        self::TRANSFER_PROCESSED                => 46,
        self::ACCOUNT_SUSPENDED                 => 47,
        self::ACCOUNT_FUNDS_HOLD                => 48,
        self::ACCOUNT_FUNDS_UNHOLD              => 49,
        self::ACCOUNT_INTERNATIONAL_ENABLED     => 50,
        self::ACCOUNT_INTERNATIONAL_DISABLED    => 51,
        self::ACCOUNT_INSTANTLY_ACTIVATED       => 52,
        self::ACCOUNT_UNDER_REVIEW              => 53,
        self::ACCOUNT_NEEDS_CLARIFICATION       => 54,
        self::ACCOUNT_ACTIVATED                 => 55,
        self::ACCOUNT_REJECTED                  => 56,
        self::ACCOUNT_PAYMENTS_ENABLED          => 57,
        self::ACCOUNT_PAYMENTS_DISABLED         => 58,
        self::TRANSACTION_UPDATED               => 59,
        self::PAYOUT_UPDATED                    => 60,
        self::PAYOUT_REJECTED                   => 61,
        self::PAYMENT_CREATED                   => 62,
        self::PAYOUT_PENDING                    => 63,
    ];

    // Add new webhook events in this array.
    public static $bitPosition2 = [
        self::PAYOUT_LINK_ISSUED                => 1,
        self::PAYOUT_LINK_PROCESSING            => 2,
        self::PAYOUT_LINK_CANCELLED             => 3,
        self::PAYOUT_LINK_ATTEMPTED             => 4,
        self::PAYOUT_LINK_PROCESSED             => 5,
        self::TERMINAL_CREATED                  => 6,
        self::PAYMENT_LINK_PAID                 => 7,
        self::PAYMENT_LINK_PARTIALLY_PAID       => 8,
        self::PAYMENT_LINK_EXPIRED              => 9,
        self::PAYMENT_LINK_CANCELLED            => 10,
        self::SUBSCRIPTION_AUTHENTICATED        => 11,
        self::TOKEN_PAUSED                      => 12,
        self::TOKEN_CANCELLED                   => 13,
        self::SUBSCRIPTION_PAUSED               => 14,
        self::SUBSCRIPTION_RESUMED              => 15,
        self::FUND_ACCOUNT_VALIDATION_FAILED    => 16,
        self::TRANSFER_SETTLED                  => 17,
        self::PAYMENT_PAGE_PAID                 => 18,
        self::REFUND_ARN_UPDATED                => 19,
        self::BANKING_ACCOUNTS_ISSUED           => 20,
        self::TRANSFER_FAILED                   => 21,
        self::PAYOUT_DOWNTIME_STARTED           => 22,
        self::PAYOUT_DOWNTIME_RESOLVED          => 23,
        self::QR_CODE_CLOSED                    => 24,
        self::QR_CODE_CREATED                   => 25,
        self::QR_CODE_CREDITED                  => 26,

        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED           => 27,
        self::PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION => 28,
        self::PAYMENT_GATEWAY_PRODUCT_REJECTED            => 29,
        self::PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW        => 30,
        self::PAYMENT_DISPUTE_UNDER_REVIEW                => 31,
        self::PAYMENT_DISPUTE_ACTION_REQUIRED             => 32,

        self::PAYOUT_CREATION_FAILED => 33,

        self::PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED => 34,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED             => 35,
        self::PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED   => 36,
        self::PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION   => 37,
        self::PAYMENT_LINKS_PRODUCT_REJECTED              => 38,
        self::PAYMENT_LINKS_PRODUCT_UNDER_REVIEW          => 39,
        self::ZAPIER_PAYMENT_PAGE_PAID_V1                 => 40,
        self::PAYMENT_PENDING                             => 41,
        self::PAYOUT_LINK_EXPIRED                         => 42,
        self::TOKEN_SERVICE_PROVIDER_ACTIVATED            => 43,
        self::TOKEN_SERVICE_PROVIDER_SUSPENDED            => 44,
        self::TOKEN_SERVICE_PROVIDER_DEACTIVATED          => 45,
        self::TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED       => 46,
        self::SHIPROCKET_PAYMENT_PAGE_PAID_V1             => 47,
        self::PAYMENT_DOWNTIME_UPDATED                    => 48,

        self::ACCOUNT_ACTIVATED_KYC_PENDING                 => 49,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING => 50,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING   => 51,

        self::PAYOUT_LINK_PENDING                         => 52,
        self::PAYOUT_LINK_REJECTED                        => 53,

        self::NO_DOC_ONBOARDING_GMV_LIMIT_WARNING         => 54,
        self::ACCOUNT_UPDATED                             => 55,
        self::INSTANT_ACTIVATION_GMV_LIMIT_WARNING        => 56,
        self::ROUTE_PRODUCT_UNDER_REVIEW                  => 57,
        self::ROUTE_PRODUCT_ACTIVATED                     => 58,
        self::ROUTE_PRODUCT_NEEDS_CLARIFICATION           => 59,
        self::ROUTE_PRODUCT_REJECTED                      => 60,

        self::ACCOUNT_MAPPED_TO_PARTNER                   => 61,
        self::ACCOUNT_APP_AUTHORIZATION_REVOKED           => 62,
    ];

    /**
     * These are events which will displayed to merchants
     * for enabling/disabling.
     * @var array
     */
    protected static $launchedEvents = [
        self::PAYMENT_AUTHORIZED                => [Product::PRIMARY],
        self::PAYMENT_PENDING                   => [Product::PRIMARY],
        self::PAYMENT_FAILED                    => [Product::PRIMARY],
        self::PAYMENT_CAPTURED                  => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_CREATED           => [Product::PRIMARY],
        self::ORDER_PAID                        => [Product::PRIMARY],
        self::INVOICE_PAID                      => [Product::PRIMARY],
        self::INVOICE_PARTIALLY_PAID            => [Product::PRIMARY],
        self::INVOICE_EXPIRED                   => [Product::PRIMARY],
        self::SUBSCRIPTION_AUTHENTICATED        => [Product::PRIMARY],
        self::SUBSCRIPTION_PAUSED               => [Product::PRIMARY],
        self::SUBSCRIPTION_RESUMED              => [Product::PRIMARY],
        self::SUBSCRIPTION_ACTIVATED            => [Product::PRIMARY],
        self::SUBSCRIPTION_PENDING              => [Product::PRIMARY],
        self::SUBSCRIPTION_HALTED               => [Product::PRIMARY],
        self::SUBSCRIPTION_CHARGED              => [Product::PRIMARY],
        self::SUBSCRIPTION_CANCELLED            => [Product::PRIMARY],
        self::SUBSCRIPTION_COMPLETED            => [Product::PRIMARY],
        self::SUBSCRIPTION_UPDATED              => [Product::PRIMARY],
        self::TOKEN_CONFIRMED                   => [Product::PRIMARY],
        self::TOKEN_REJECTED                    => [Product::PRIMARY],
        self::TOKEN_PAUSED                      => [Product::PRIMARY],
        self::TOKEN_CANCELLED                   => [Product::PRIMARY],
        self::SETTLEMENT_PROCESSED              => [Product::PRIMARY],
        self::VIRTUAL_ACCOUNT_CREDITED          => [Product::PRIMARY],
        self::VIRTUAL_ACCOUNT_CREATED           => [Product::PRIMARY],
        self::VIRTUAL_ACCOUNT_CLOSED            => [Product::PRIMARY],
        self::QR_CODE_CLOSED                    => [Product::PRIMARY],
        self::QR_CODE_CREATED                   => [Product::PRIMARY],
        self::QR_CODE_CREDITED                  => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_WON               => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_LOST              => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_CLOSED            => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_UNDER_REVIEW      => [Product::PRIMARY],
        self::PAYMENT_DISPUTE_ACTION_REQUIRED   => [Product::PRIMARY],
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => [Product::PRIMARY, Product::BANKING],
        self::FUND_ACCOUNT_VALIDATION_FAILED    => [Product::PRIMARY, Product::BANKING],
        self::TRANSACTION_CREATED               => [Product::BANKING],
        self::PAYOUT_PROCESSED                  => [Product::PRIMARY, Product::BANKING],
        self::PAYOUT_REVERSED                   => [Product::PRIMARY, Product::BANKING],
        self::PAYOUT_FAILED                     => [Product::BANKING],
        self::PAYMENT_DOWNTIME_STARTED          => [Product::PRIMARY],
        self::PAYMENT_DOWNTIME_UPDATED          => [Product::PRIMARY],
        self::PAYMENT_DOWNTIME_RESOLVED         => [Product::PRIMARY],
        self::PAYOUT_QUEUED                     => [Product::BANKING],
        self::PAYOUT_INITIATED                  => [Product::PRIMARY, Product::BANKING],
        self::REFUND_SPEED_CHANGED              => [Product::PRIMARY],
        self::REFUND_PROCESSED                  => [Product::PRIMARY],
        self::REFUND_FAILED                     => [Product::PRIMARY],
        self::REFUND_ARN_UPDATED                => [Product::PRIMARY],
        self::TRANSACTION_UPDATED               => [Product::BANKING],
        self::REFUND_CREATED                    => [Product::PRIMARY],
        self::TRANSFER_PROCESSED                => [Product::PRIMARY],
        self::TRANSFER_SETTLED                  => [Product::PRIMARY],
        self::TRANSFER_FAILED                   => [Product::PRIMARY],
        self::TERMINAL_CREATED                  => [Product::PRIMARY],
        self::TERMINAL_ACTIVATED                => [Product::PRIMARY],
        self::TERMINAL_FAILED                   => [Product::PRIMARY],
        self::ACCOUNT_SUSPENDED                 => [Product::PRIMARY],
        self::ACCOUNT_FUNDS_HOLD                => [Product::PRIMARY],
        self::ACCOUNT_FUNDS_UNHOLD              => [Product::PRIMARY],
        self::ACCOUNT_INTERNATIONAL_ENABLED     => [Product::PRIMARY],
        self::ACCOUNT_INTERNATIONAL_DISABLED    => [Product::PRIMARY],
        self::ACCOUNT_INSTANTLY_ACTIVATED       => [Product::PRIMARY],
        self::ACCOUNT_ACTIVATED_KYC_PENDING     => [Product::PRIMARY],
        self::ACCOUNT_UNDER_REVIEW              => [Product::PRIMARY],
        self::ACCOUNT_NEEDS_CLARIFICATION       => [Product::PRIMARY],
        self::ACCOUNT_ACTIVATED                 => [Product::PRIMARY],
        self::ACCOUNT_REJECTED                  => [Product::PRIMARY],
        self::ACCOUNT_UPDATED                   => [Product::PRIMARY],
        self::ACCOUNT_PAYMENTS_ENABLED          => [Product::PRIMARY],
        self::ACCOUNT_PAYMENTS_DISABLED         => [Product::PRIMARY],
        self::ACCOUNT_MAPPED_TO_PARTNER         => [Product::PRIMARY],
        self::ACCOUNT_APP_AUTHORIZATION_REVOKED => [Product::PRIMARY],
        self::PAYOUT_UPDATED                    => [Product::PRIMARY, Product::BANKING],
        self::PAYOUT_REJECTED                   => [Product::PRIMARY, Product::BANKING],
        self::PAYMENT_CREATED                   => [Product::PRIMARY],
        self::PAYOUT_PENDING                    => [Product::PRIMARY, Product::BANKING],
        self::PAYOUT_LINK_ISSUED                => [Product::BANKING],
        self::PAYOUT_LINK_PROCESSING            => [Product::BANKING],
        self::PAYOUT_LINK_PROCESSED             => [Product::BANKING],
        self::PAYOUT_LINK_ATTEMPTED             => [Product::BANKING],
        self::PAYOUT_LINK_CANCELLED             => [Product::BANKING],
        self::PAYOUT_LINK_EXPIRED               => [Product::BANKING],
        self::PAYOUT_LINK_PENDING               => [Product::BANKING],
        self::PAYOUT_LINK_REJECTED              => [Product::BANKING],
        self::PAYMENT_LINK_PAID                 => [Product::PRIMARY],
        self::PAYMENT_LINK_PARTIALLY_PAID       => [Product::PRIMARY],
        self::PAYMENT_LINK_EXPIRED              => [Product::PRIMARY],
        self::PAYMENT_LINK_CANCELLED            => [Product::PRIMARY],
        self::BANKING_ACCOUNTS_ISSUED           => [Product::PRIMARY],
        self::P2P_TRANSACTION_CREATED           => [Product::PRIMARY],
        self::P2P_TRANSACTION_COMPLETED         => [Product::PRIMARY],
        self::P2P_TRANSACTION_FAILED            => [Product::PRIMARY],
        self::P2P_VPA_CREATED                   => [Product::PRIMARY],
        self::P2P_VPA_DELETED                   => [Product::PRIMARY],
        self::P2P_VERIFICATION_COMPLETED        => [Product::PRIMARY],
        self::P2P_DEREGISTRATION_COMPLETED      => [Product::PRIMARY],
        self::PAYOUT_DOWNTIME_STARTED           => [Product::BANKING],
        self::PAYOUT_DOWNTIME_RESOLVED          => [Product::BANKING],
        self::PAYOUT_CREATION_FAILED            => [Product::BANKING],
        self::ZAPIER_PAYMENT_PAGE_PAID_V1       => [Product::PRIMARY],
        self::SHIPROCKET_PAYMENT_PAGE_PAID_V1   => [Product::PRIMARY],

        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED           => [Product::PRIMARY],
        self::PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION => [Product::PRIMARY],
        self::PAYMENT_GATEWAY_PRODUCT_REJECTED            => [Product::PRIMARY],
        self::PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW        => [Product::PRIMARY],
        self::PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED => [Product::PRIMARY],
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED             => [Product::PRIMARY],
        self::PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED   => [Product::PRIMARY],
        self::PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION   => [Product::PRIMARY],
        self::PAYMENT_LINKS_PRODUCT_REJECTED              => [Product::PRIMARY],
        self::PAYMENT_LINKS_PRODUCT_UNDER_REVIEW          => [Product::PRIMARY],
        self::ROUTE_PRODUCT_UNDER_REVIEW                  => [Product::PRIMARY],
        self::ROUTE_PRODUCT_ACTIVATED                     => [Product::PRIMARY],
        self::ROUTE_PRODUCT_NEEDS_CLARIFICATION           => [Product::PRIMARY],
        self::ROUTE_PRODUCT_REJECTED                      => [Product::PRIMARY],
        self::TOKEN_SERVICE_PROVIDER_ACTIVATED            => [Product::PRIMARY],
        self::TOKEN_SERVICE_PROVIDER_SUSPENDED            => [Product::PRIMARY],
        self::TOKEN_SERVICE_PROVIDER_DEACTIVATED          => [Product::PRIMARY],
        self::TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED       => [Product::PRIMARY],
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING => [Product::PRIMARY],
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING   => [Product::PRIMARY],
        self::NO_DOC_ONBOARDING_GMV_LIMIT_WARNING           => [Product::PRIMARY],
        self::INSTANT_ACTIVATION_GMV_LIMIT_WARNING          => [Product::PRIMARY],

        self::ISSUING_LOAD_CREATED                        => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_LOAD_SUCCESS                        => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_LOAD_FAILED                         => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_WITHDRAWAL_CREATED                  => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_WITHDRAWAL_INITIATED                => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_WITHDRAWAL_PROCESSED                => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_WITHDRAWAL_FAILED                   => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_KYC_SUCCESS                         => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_KYC_MANUAL_REVIEW                   => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_KYC_MANUALLY_VERIFIED               => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_KYC_FAILED                          => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_KYC_IN_PROGRESS                     => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_BENEFICIARY_CREATED                 => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_BENEFICIARY_ACTIVE                  => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_BENEFICIARY_MANUAL_REVIEW           => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_BENEFICIARY_FAILED                  => [Product::PRIMARY, Product::ISSUING],
        self::ISSUING_TRANSACTION_CREATED                 => [Product::PRIMARY, Product::ISSUING],
    ];

    /**
     * Defines the mapping to main entity for respective event and also
     * the field description to be set in mail content for webhook related mails
     *
     * @var array
     */
    public static $eventsToEntityMap = [
        self::PAYMENT_AUTHORIZED                => Entity::PAYMENT,
        self::PAYMENT_PENDING                   => Entity::PAYMENT,
        self::PAYMENT_CAPTURED                  => Entity::PAYMENT,
        self::PAYMENT_FAILED                    => Entity::PAYMENT,
        self::PAYMENT_DISPUTE_CREATED           => Entity::PAYMENT,
        self::VIRTUAL_ACCOUNT_CREDITED          => Entity::PAYMENT,
        self::VIRTUAL_ACCOUNT_CREATED           => Entity::VIRTUAL_ACCOUNT,
        self::VIRTUAL_ACCOUNT_CLOSED            => Entity::VIRTUAL_ACCOUNT,
        self::QR_CODE_CLOSED                    => Entity::QR_CODE,
        self::QR_CODE_CREATED                   => Entity::QR_CODE,
        self::QR_CODE_CREDITED                  => Entity::PAYMENT,
        self::INVOICE_PAID                      => Entity::INVOICE,
        self::INVOICE_PARTIALLY_PAID            => Entity::INVOICE,
        self::INVOICE_EXPIRED                   => Entity::INVOICE,
        self::ORDER_PAID                        => Entity::ORDER,
        self::SUBSCRIPTION_AUTHENTICATED        => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_PAUSED               => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_RESUMED              => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_ACTIVATED            => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_PENDING              => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_HALTED               => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_CHARGED              => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_CANCELLED            => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_COMPLETED            => Entity::SUBSCRIPTION,
        self::SUBSCRIPTION_UPDATED              => Entity::SUBSCRIPTION,
        self::TOKEN_CONFIRMED                   => Entity::TOKEN,
        self::TOKEN_REJECTED                    => Entity::TOKEN,
        self::TOKEN_PAUSED                      => Entity::TOKEN,
        self::TOKEN_CANCELLED                   => Entity::TOKEN,
        self::SETTLEMENT_PROCESSED              => Entity::SETTLEMENT,
        self::PAYMENT_DISPUTE_WON               => Entity::DISPUTE,
        self::PAYMENT_DISPUTE_LOST              => Entity::DISPUTE,
        self::PAYMENT_DISPUTE_CLOSED            => Entity::DISPUTE,
        self::PAYMENT_DISPUTE_UNDER_REVIEW      => Entity::DISPUTE,
        self::PAYMENT_DISPUTE_ACTION_REQUIRED   => Entity::DISPUTE,
        self::TRANSACTION_CREATED               => Entity::TRANSACTION,
        self::PAYOUT_PROCESSED                  => Entity::PAYOUT,
        self::PAYOUT_REVERSED                   => Entity::PAYOUT,
        self::PAYOUT_FAILED                     => Entity::PAYOUT,
        self::FUND_ACCOUNT_VALIDATION_COMPLETED => FundAccount\Validation\Entity::PUBLIC_ENTITY_NAME,
        self::FUND_ACCOUNT_VALIDATION_FAILED    => FundAccount\Validation\Entity::PUBLIC_ENTITY_NAME,
        self::PAYMENT_DOWNTIME_STARTED          => Entity::PAYMENT_DOWNTIME,
        self::PAYMENT_DOWNTIME_UPDATED          => Entity::PAYMENT_DOWNTIME,
        self::PAYMENT_DOWNTIME_RESOLVED         => Entity::PAYMENT_DOWNTIME,
        self::PAYOUT_QUEUED                     => Entity::PAYOUT,
        self::PAYOUT_INITIATED                  => Entity::PAYOUT,
        self::REFUND_SPEED_CHANGED              => Entity::REFUND,
        self::REFUND_PROCESSED                  => Entity::REFUND,
        self::REFUND_FAILED                     => Entity::REFUND,
        self::REFUND_ARN_UPDATED                => Entity::REFUND,
        self::TRANSACTION_UPDATED               => Entity::TRANSACTION,
        self::REFUND_CREATED                    => Entity::REFUND,
        self::TRANSFER_PROCESSED                => Entity::TRANSFER,
        self::TRANSFER_SETTLED                  => Entity::SETTLEMENT,
        self::TRANSFER_FAILED                   => Entity::TRANSFER,
        self::TERMINAL_CREATED                  => Entity::TERMINAL,
        self::TERMINAL_ACTIVATED                => Entity::TERMINAL,
        self::TERMINAL_FAILED                   => Entity::TERMINAL,
        self::ACCOUNT_SUSPENDED                 => Entity::MERCHANT,
        self::ACCOUNT_FUNDS_HOLD                => Entity::MERCHANT,
        self::ACCOUNT_FUNDS_UNHOLD              => Entity::MERCHANT,
        self::ACCOUNT_INTERNATIONAL_ENABLED     => Entity::MERCHANT,
        self::ACCOUNT_INTERNATIONAL_DISABLED    => Entity::MERCHANT,
        self::ACCOUNT_INSTANTLY_ACTIVATED       => Entity::MERCHANT,
        self::ACCOUNT_ACTIVATED_KYC_PENDING     => Entity::MERCHANT,
        self::ACCOUNT_UNDER_REVIEW              => Entity::MERCHANT,
        self::ACCOUNT_NEEDS_CLARIFICATION       => Entity::MERCHANT,
        self::ACCOUNT_ACTIVATED                 => Entity::MERCHANT,
        self::ACCOUNT_REJECTED                  => Entity::MERCHANT,
        self::ACCOUNT_UPDATED                   => Entity::ACCOUNT,
        self::ACCOUNT_PAYMENTS_ENABLED          => Entity::MERCHANT,
        self::ACCOUNT_PAYMENTS_DISABLED         => Entity::MERCHANT,
        self::ACCOUNT_MAPPED_TO_PARTNER         => Entity::MERCHANT,
        self::NO_DOC_ONBOARDING_GMV_LIMIT_WARNING => Entity::MERCHANT,
        self::PAYOUT_LINK_ISSUED                => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_PROCESSED             => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_PROCESSING            => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_CANCELLED             => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_ATTEMPTED             => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_EXPIRED               => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_PENDING               => Entity::PAYOUT_LINK,
        self::PAYOUT_LINK_REJECTED              => Entity::PAYOUT_LINK,
        self::PAYOUT_UPDATED                    => Entity::PAYOUT,
        self::PAYOUT_REJECTED                   => Entity::PAYOUT,
        self::PAYMENT_CREATED                   => Entity::PAYMENT,
        self::PAYOUT_PENDING                    => Entity::PAYOUT,
        self::BANKING_ACCOUNTS_ISSUED           => Entity::MERCHANT,
        self::PAYMENT_PAGE_PAID                 => Entity::PAYMENT_PAGE,
        self::ZAPIER_PAYMENT_PAGE_PAID_V1       => Entity::PAYMENT_PAGE,
        self::SHIPROCKET_PAYMENT_PAGE_PAID_V1   => Entity::PAYMENT_PAGE,
        self::P2P_TRANSACTION_CREATED           => Entity::P2P_TRANSACTION,
        self::P2P_TRANSACTION_COMPLETED         => Entity::P2P_TRANSACTION,
        self::P2P_TRANSACTION_FAILED            => Entity::P2P_TRANSACTION,
        self::P2P_VPA_CREATED                   => Entity::P2P_VPA,
        self::P2P_VPA_DELETED                   => Entity::P2P_VPA,
        self::P2P_VERIFICATION_COMPLETED        => Entity::P2P_DEVICE,
        self::P2P_DEREGISTRATION_COMPLETED      => Entity::P2P_DEVICE,

        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED           => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_GATEWAY_PRODUCT_REJECTED            => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW        => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED             => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED   => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION   => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_LINKS_PRODUCT_REJECTED              => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_LINKS_PRODUCT_UNDER_REVIEW          => Entity::MERCHANT_PRODUCT,
        self::ROUTE_PRODUCT_UNDER_REVIEW                  => Entity::MERCHANT_PRODUCT,
        self::ROUTE_PRODUCT_ACTIVATED                     => Entity::MERCHANT_PRODUCT,
        self::ROUTE_PRODUCT_NEEDS_CLARIFICATION           => Entity::MERCHANT_PRODUCT,
        self::ROUTE_PRODUCT_REJECTED                      => Entity::MERCHANT_PRODUCT,
        self::TOKEN_SERVICE_PROVIDER_ACTIVATED            => Entity::TOKEN,
        self::TOKEN_SERVICE_PROVIDER_SUSPENDED            => Entity::TOKEN,
        self::TOKEN_SERVICE_PROVIDER_DEACTIVATED          => Entity::TOKEN,
        self::TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED       => Entity::TOKEN,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING => Entity::MERCHANT_PRODUCT,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING   => Entity::MERCHANT_PRODUCT,
        self::INSTANT_ACTIVATION_GMV_LIMIT_WARNING          => Entity::MERCHANT,
    ];

    public static $eventsToFeatureMap = [
        self::SUBSCRIPTION_AUTHENTICATED        => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_ACTIVATED            => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_PENDING              => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_HALTED               => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_PAUSED               => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_RESUMED              => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_CHARGED              => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_CANCELLED            => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_COMPLETED            => Feature\Constants::SUBSCRIPTIONS,
        self::SUBSCRIPTION_UPDATED              => Feature\Constants::SUBSCRIPTIONS,
        self::TOKEN_CONFIRMED                   => Feature\Constants::CHARGE_AT_WILL,
        self::TOKEN_REJECTED                    => Feature\Constants::CHARGE_AT_WILL,
        self::TOKEN_PAUSED                      => Feature\Constants::CHARGE_AT_WILL,
        self::TOKEN_CANCELLED                   => Feature\Constants::CHARGE_AT_WILL,
        self::VIRTUAL_ACCOUNT_CREDITED          => Feature\Constants::VIRTUAL_ACCOUNTS,
        self::VIRTUAL_ACCOUNT_CREATED           => Feature\Constants::VIRTUAL_ACCOUNTS,
        self::VIRTUAL_ACCOUNT_CLOSED            => Feature\Constants::VIRTUAL_ACCOUNTS,
        self::SETTLEMENT_PROCESSED              => Feature\Constants::MARKETPLACE,
        self::PAYOUT_PROCESSED                  => Feature\Constants::PAYOUT,
        self::PAYOUT_REVERSED                   => Feature\Constants::PAYOUT,
        self::PAYOUT_FAILED                     => Feature\Constants::PAYOUT,
        self::PAYOUT_QUEUED                     => Feature\Constants::PAYOUT,
        self::PAYOUT_INITIATED                  => Feature\Constants::PAYOUT,
        self::PAYOUT_CREATION_FAILED            => Feature\Constants::PAYOUTS_BATCH,
        self::TRANSFER_PROCESSED                => Feature\Constants::MARKETPLACE,
        self::TRANSFER_SETTLED                  => Feature\Constants::TRANSFER_SETTLED_WEBHOOK,
        self::TRANSFER_FAILED                   => Feature\Constants::MARKETPLACE,
        self::TERMINAL_CREATED                  => Feature\Constants::TERMINAL_ONBOARDING,
        self::TERMINAL_ACTIVATED                => Feature\Constants::TERMINAL_ONBOARDING,
        self::TERMINAL_FAILED                   => Feature\Constants::TERMINAL_ONBOARDING,
        self::PAYOUT_UPDATED                    => Feature\Constants::PAYOUT,
        self::PAYOUT_REJECTED                   => Feature\Constants::PAYOUT,
        self::ACCOUNT_SUSPENDED                 => [Feature\Constants::SUBMERCHANT_ONBOARDING, Feature\Constants::SUBMERCHANT_ONBOARDING_V2],
        self::ACCOUNT_FUNDS_HOLD                => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_FUNDS_UNHOLD              => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_INTERNATIONAL_ENABLED     => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_INTERNATIONAL_DISABLED    => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_INSTANTLY_ACTIVATED       => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_ACTIVATED_KYC_PENDING     => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_UNDER_REVIEW              => [Feature\Constants::SUBMERCHANT_ONBOARDING, Feature\Constants::MARKETPLACE],
        self::ACCOUNT_NEEDS_CLARIFICATION       => [Feature\Constants::SUBMERCHANT_ONBOARDING, Feature\Constants::MARKETPLACE],
        self::ACCOUNT_ACTIVATED                 => [Feature\Constants::SUBMERCHANT_ONBOARDING, Feature\Constants::MARKETPLACE],
        self::ACCOUNT_REJECTED                  => [Feature\Constants::SUBMERCHANT_ONBOARDING, Feature\Constants::MARKETPLACE],
        self::ACCOUNT_PAYMENTS_ENABLED          => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_PAYMENTS_DISABLED         => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_MAPPED_TO_PARTNER         => Feature\Constants::SUBMERCHANT_ONBOARDING,
        self::ACCOUNT_UPDATED                   => [Feature\Constants::MARKETPLACE, Feature\Constants::LA_BANK_ACCOUNT_UPDATE],
        self::PAYOUT_PENDING                    => Feature\Constants::PAYOUT,
        self::PAYMENT_CREATED                   => Feature\Constants::PAYMENT_CREATED_WEBHOOK,
        self::PAYOUT_LINK_ISSUED                => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_ATTEMPTED             => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_PROCESSED             => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_PROCESSING            => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_CANCELLED             => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_EXPIRED               => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_PENDING               => Feature\Constants::PAYOUT,
        self::PAYOUT_LINK_REJECTED              => Feature\Constants::PAYOUT,
        self::BANKING_ACCOUNTS_ISSUED           => Feature\Constants::BANKING_ACCOUNTS_ISSUED,
        self::REFUND_ARN_UPDATED                => Feature\Constants::REFUND_ARN_WEBHOOK,
        self::P2P_TRANSACTION_CREATED           => Feature\Constants::P2P_UPI,
        self::P2P_TRANSACTION_COMPLETED         => Feature\Constants::P2P_UPI,
        self::P2P_TRANSACTION_FAILED            => Feature\Constants::P2P_UPI,
        self::P2P_VPA_CREATED                   => Feature\Constants::P2P_UPI,
        self::P2P_VPA_DELETED                   => Feature\Constants::P2P_UPI,
        self::P2P_VERIFICATION_COMPLETED        => Feature\Constants::P2P_UPI,
        self::P2P_DEREGISTRATION_COMPLETED      => Feature\Constants::P2P_UPI,
        self::QR_CODE_CLOSED                    => Feature\Constants::QR_CODES,
        self::QR_CODE_CREATED                   => Feature\Constants::QR_CODES,
        self::QR_CODE_CREDITED                  => Feature\Constants::QR_CODES,

        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED           => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_GATEWAY_PRODUCT_NEEDS_CLARIFICATION => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_GATEWAY_PRODUCT_REJECTED            => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_GATEWAY_PRODUCT_UNDER_REVIEW        => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_GATEWAY_PRODUCT_INSTANTLY_ACTIVATED => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED             => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_LINKS_PRODUCT_INSTANTLY_ACTIVATED   => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_LINKS_PRODUCT_NEEDS_CLARIFICATION   => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_LINKS_PRODUCT_REJECTED              => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_LINKS_PRODUCT_UNDER_REVIEW          => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::ROUTE_PRODUCT_UNDER_REVIEW                  => Feature\Constants::MARKETPLACE,
        self::ROUTE_PRODUCT_ACTIVATED                     => Feature\Constants::MARKETPLACE,
        self::ROUTE_PRODUCT_NEEDS_CLARIFICATION           => Feature\Constants::MARKETPLACE,
        self::ROUTE_PRODUCT_REJECTED                      => Feature\Constants::MARKETPLACE,
        self::TOKEN_SERVICE_PROVIDER_ACTIVATED            => Feature\Constants::NETWORK_TOKENIZATION_LIVE,
        self::TOKEN_SERVICE_PROVIDER_SUSPENDED            => Feature\Constants::NETWORK_TOKENIZATION_LIVE,
        self::TOKEN_SERVICE_PROVIDER_DEACTIVATED          => Feature\Constants::NETWORK_TOKENIZATION_LIVE,
        self::TOKEN_SERVICE_PROVIDER_EXPIRY_UPDATED       => Feature\Constants::NETWORK_TOKENIZATION_LIVE,
        self::PAYMENT_GATEWAY_PRODUCT_ACTIVATED_KYC_PENDING => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_LINKS_PRODUCT_ACTIVATED_KYC_PENDING   => Feature\Constants::SUBMERCHANT_ONBOARDING_V2,
        self::PAYMENT_PENDING                               => Feature\Constants::ONE_CLICK_CHECKOUT,
        self::NO_DOC_ONBOARDING_GMV_LIMIT_WARNING         => Feature\Constants::SUBM_NO_DOC_ONBOARDING,
        self::INSTANT_ACTIVATION_GMV_LIMIT_WARNING        => Feature\Constants::INSTANT_ACTIVATION_V2_API,

        self::ISSUING_LOAD_CREATED                        => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_LOAD_SUCCESS                        => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_LOAD_FAILED                         => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_WITHDRAWAL_CREATED                  => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_WITHDRAWAL_INITIATED                => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_WITHDRAWAL_PROCESSED                => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_WITHDRAWAL_FAILED                   => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_KYC_SUCCESS                         => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_KYC_MANUAL_REVIEW                   => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_KYC_MANUALLY_VERIFIED               => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_KYC_FAILED                          => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_KYC_IN_PROGRESS                     => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_BENEFICIARY_CREATED                 => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_BENEFICIARY_ACTIVE                  => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_BENEFICIARY_MANUAL_REVIEW           => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_BENEFICIARY_FAILED                  => Feature\Constants::RAZORPAY_WALLET,
        self::ISSUING_TRANSACTION_CREATED                 => Feature\Constants::RAZORPAY_WALLET,
    ];

    public static $eventsToPartnerTypeMap = [
        self::ACCOUNT_APP_AUTHORIZATION_REVOKED        => [Merchant\Constants::PURE_PLATFORM],
        self::ACCOUNT_ACTIVATED                        => [Merchant\Constants::PURE_PLATFORM],
        self::ACCOUNT_ACTIVATED_KYC_PENDING            => [Merchant\Constants::PURE_PLATFORM],
        self::ACCOUNT_NEEDS_CLARIFICATION              => [Merchant\Constants::PURE_PLATFORM],
        self::ACCOUNT_REJECTED                         => [Merchant\Constants::PURE_PLATFORM],
        self::ACCOUNT_SUSPENDED                        => [Merchant\Constants::PURE_PLATFORM],
        self::ACCOUNT_UNDER_REVIEW                     => [Merchant\Constants::PURE_PLATFORM],
    ];

    public static array $eventsApplicableBasedOnFeatureOrPartnerType = [
        self::ACCOUNT_REJECTED,
        self::ACCOUNT_SUSPENDED,
        self::ACCOUNT_ACTIVATED,
        self::ACCOUNT_UNDER_REVIEW,
        self::ACCOUNT_NEEDS_CLARIFICATION,
        self::ACCOUNT_ACTIVATED_KYC_PENDING
    ];

    /**
     * Defines the list of webhooks that needs to be hidden from fetch events api
     * This is to stop merchants from manually subscribing to these webhooks.
     * OAuth applications, if they have the name of the webhook can directly subscribe.
     *
     * @var array
     */
    public static $eventsSkippedFromListingApi = [
        self::ZAPIER_PAYMENT_PAGE_PAID_V1,
        self::SHIPROCKET_PAYMENT_PAGE_PAID_V1,
    ];

    public static function getLaunchedEventNames()
    {
        return self::$launchedEvents;
    }

    public static function validateEventName(string $event): bool
    {
        return (in_array($event, self::$names) === true);
    }

    /**
     * Filters and returns events to be exposed in public api response.
     *
     * @param  Merchant\Entity $merchant
     * @param  array|null      $events
     * @return array
     */
    public static function filterForPublicApi(Merchant\Entity $merchant, array $events = null)
    {
        $originalEvents = ($events !== null) ?
                          $events :
                          static::getLaunchedEventNames();

        $productFilteredEvents = static::filterByProductOrigin($originalEvents);

        $featureFilteredEvents = static::filterByFeatures($productFilteredEvents, $merchant->getEnabledFeatures());

        $partnerTypeFilteredEvents = static::filterByPartnerType($featureFilteredEvents, $merchant->getPartnerType());

        return array_merge($partnerTypeFilteredEvents, static::getFeaturesAvailableByFeatureOrPartnerType($productFilteredEvents, $merchant));
    }

    public static function getFeaturesAvailableByFeatureOrPartnerType($originalEvents, Merchant\Entity $merchant) : array
    {
        $eventsListApplicable = Event::$eventsApplicableBasedOnFeatureOrPartnerType;

        $events = array_filter($originalEvents, function ($key, $value) use ($eventsListApplicable)
        {
            return in_array($value, $eventsListApplicable);
        }, ARRAY_FILTER_USE_BOTH);

        $featureFilteredEvents = static::filterByFeatures($events, $merchant->getEnabledFeatures());

        $productFilteredEvents = static::filterByPartnerType($events, $merchant->getPartnerType());

        return array_merge($featureFilteredEvents, $productFilteredEvents);
    }

    public static function filterByProductOrigin(array $events): array
    {
        $product = app('basicauth')->getRequestOriginProduct() ?? Product::PRIMARY;

        $productEvents = group_array_by_value_array($product, self::getLaunchedEventNames())[$product];

        return array_filter(
            $events,
            function($event) use ($productEvents)
            {
                return (in_array($event, $productEvents, true) === true);
            }, ARRAY_FILTER_USE_KEY);
    }

    public static function filterByFeatures(array $eventNames, array $merchantAssignedFeatures): array
    {
        $featureMap = Event::$eventsToFeatureMap;

        foreach ($eventNames as $eventName => $value)
        {
            $removeEvent = false;

            if ((isset($featureMap[$eventName]) === true))
            {
                $featureMapValue = $featureMap[$eventName];

                if (is_string($featureMapValue) === true)
                {
                    $featureMapValue = [$featureMapValue];
                }

                $assignedFeatures = array_intersect($featureMapValue, $merchantAssignedFeatures);

                if (count($assignedFeatures) === 0)
                {
                    $removeEvent = true;
                }

                if ($removeEvent === true)
                {
                    unset($eventNames[$eventName]);
                }
            }

        }

        return $eventNames;
    }

    public static function filterByPartnerType(array $eventNames, string $merchantPartnerType = null): array
    {
        $eventPartnerMap = Event::$eventsToPartnerTypeMap;

        foreach ($eventNames as $eventName => $value)
        {
            $removeEvent = false;

            if ((isset($eventPartnerMap[$eventName]) === true))
            {
                $eventPartnerMapValue = $eventPartnerMap[$eventName];

                if (array_search($merchantPartnerType, $eventPartnerMapValue) === false)
                {
                    $removeEvent = true;
                }

                if ($removeEvent === true)
                {
                    unset($eventNames[$eventName]);
                }
            }
        }

        return $eventNames;
    }

    public static function getEventsSkippedFromListingApi(): array
    {
        return self::$eventsSkippedFromListingApi;
    }
}
