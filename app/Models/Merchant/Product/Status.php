<?php

namespace RZP\Models\Merchant\Product;

use RZP\Models\Merchant\Detail\Status as MerchantActivationStatus;

class Status
{
    const PAYMENT_GATEWAY_PRODUCT_STATUS_MAPPING = [
        MerchantActivationStatus::ACTIVATED             => MerchantActivationStatus::ACTIVATED,
        MerchantActivationStatus::REJECTED              => MerchantActivationStatus::REJECTED,
        MerchantActivationStatus::UNDER_REVIEW          => MerchantActivationStatus::UNDER_REVIEW,
        MerchantActivationStatus::NEEDS_CLARIFICATION   => MerchantActivationStatus::NEEDS_CLARIFICATION,
        MerchantActivationStatus::ACTIVATED_MCC_PENDING => MerchantActivationStatus::ACTIVATED,
    ];

    //status
    const REQUESTED           = 'requested';
    const NEEDS_CLARIFICATION = 'needs_clarification';
}
