<?php

namespace RZP\Models\Merchant\Product;

use RZP\Models\Merchant\Detail\Status as MerchantActivationStatus;

class Status
{
    const PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING = [
        MerchantActivationStatus::ACTIVATED             => MerchantActivationStatus::ACTIVATED,
        MerchantActivationStatus::INSTANTLY_ACTIVATED   => MerchantActivationStatus::INSTANTLY_ACTIVATED,
        MerchantActivationStatus::REJECTED              => MerchantActivationStatus::REJECTED,
        MerchantActivationStatus::UNDER_REVIEW          => MerchantActivationStatus::UNDER_REVIEW,
        MerchantActivationStatus::NEEDS_CLARIFICATION   => MerchantActivationStatus::NEEDS_CLARIFICATION,
        MerchantActivationStatus::ACTIVATED_MCC_PENDING => MerchantActivationStatus::ACTIVATED,
        MerchantActivationStatus::ACTIVATED_KYC_PENDING => MerchantActivationStatus::ACTIVATED_KYC_PENDING,
    ];

    //Payment links and payment gateway product status are same
    const PAYMENT_LINKS_PRODUCT_STATUS_MAPPING = self::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING;

    const ROUTE_PRODUCT_STATUS_MAPPING         = self::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING;

    const PAYMENT_GATEWAY_TERMINAL_STATUS = [MerchantActivationStatus::ACTIVATED, MerchantActivationStatus::REJECTED];

    const PAYMENT_LINKS_TERMINAL_STATUS = [MerchantActivationStatus::ACTIVATED, MerchantActivationStatus::REJECTED];

    const MERCHANT_STATUS_ASSOCIATED_PRODUCTS = [
        Name::PAYMENT_GATEWAY,
        Name::PAYMENT_LINKS,
        Name::ROUTE,
    ];

    const PRODUCT_NAME_STATUS_MAPPING = [
        Name::PAYMENT_GATEWAY => self::PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING,
        Name::PAYMENT_LINKS   => self::PAYMENT_LINKS_PRODUCT_STATUS_MAPPING,
        Name::ROUTE           => self::ROUTE_PRODUCT_STATUS_MAPPING,
    ];

    //status
    const REQUESTED           = 'requested';
    const ACTIVATED           = 'activated';
    const NEEDS_CLARIFICATION = 'needs_clarification';

    //Possible status update sources for a merchant_product
    const DOCUMENT_SOURCE       = 'document';
    const ACCOUNT_SOURCE        = 'account';
    const PRODUCT_CONFIG_SOURCE = 'product_config';
    const STAKEHOLDER_SOURCE    = 'stakeholder';
    const TNC_SOURCE            = 'tnc';
    const OTP_SOURCE            = 'otp';
}
