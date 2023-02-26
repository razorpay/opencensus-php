<?php

namespace RZP\Models\Merchant\Invoice;

class Type
{
    // Commission on Card Payments <= INR 2,000'
    const CARD_LTE_2K           = 'card_lte_2k';

    // Commission on Card Payments > INR 2,000
    const CARD_GT_2K            = 'card_gt_2k';

    // Commission on All Methods Except Cards
    const OTHERS                = 'others';

    // Commission on Razorpay-X Transactions
    const RX_TRANSACTIONS       = 'rx_transactions';

    // Adjustment on Razorpay-X Transactions
    const RX_ADJUSTMENTS        = 'rx_adjustments';

    // Commission on All Validations
    const VALIDATION            = 'validation';

    // Commission on Pricing Bundle fee charged
    const PRICING_BUNDLE        = 'pricing_bundle';

    // Fee on Instant Refunds
    const INSTANT_REFUNDS       = 'instant_refunds';

    // Fee on Instant Refund <= INR 1,000 - This is kept to support older invoice
    const REFUND_LTE_1K         = 'refund_lte_1k';

    // Fee on Instant Refund > INR 1,000 & <= INR 10,000 - This is kept to support older invoice
    const REFUND_GT_1K_LTE_10K  = 'refund_gt_1k_lte_10k';

    // Fee on Instant Refund > INR 10,000 - This is kept to support older invoice
    const REFUND_GT_10K         = 'refund_gt_10k';

    // This is kept to support older invoice
    const NON_CARD              = 'non_card';

    // Any adjustments made for commission
    const ADJUSTMENT            = 'adjustment';

    const DEFAULT_GST_SAC_CODE = '';

    protected static $typeToSacMap = [
        self::CARD_LTE_2K   => 997158,
        self::CARD_GT_2K    => 997158,
        self::OTHERS        => 997158,
        self::ADJUSTMENT    => 997158,
        self::VALIDATION    => 997158,
        self::PRICING_BUNDLE       => 997158,
        self::RX_TRANSACTIONS      => 997158,
        self::RX_ADJUSTMENTS       => 997158,
        self::INSTANT_REFUNDS      => 997158,
        // This is kept to support older invoice
        self::NON_CARD             => 997158,
        // This is kept to support older invoice
        self::REFUND_LTE_1K        => 997158,
        // This is kept to support older invoice
        self::REFUND_GT_1K_LTE_10K => 997158,
        // This is kept to support older invoice
        self::REFUND_GT_10K        => 997158,
    ];

    const DEFAULT_DESCRIPTION = 'Commission';

    const CARD_LTE_2K_DESCRIPTION           = 'Commission on Card Payments <= INR 2,000';
    const CARD_GT_2K_DESCRIPTION            = 'Commission on Card Payments > INR 2,000';
    const OTHERS_DESCRIPTION                = 'Commission on All Methods Except Cards';
    const RX_TRANSACTIONS_DESCRIPTION       = 'RazorpayX Transactions Fees';
    const RX_ADJUSTMENTS_DESCRIPTION        = 'RazorpayX Adjustments';
    const VALIDATION_DESCRIPTION            = 'Commission on All Validations';
    const PRICING_BUNDLE_DESCRIPTION        = 'Fee for pricing bundle subscription';
    const INSTANT_REFUNDS_DESCRIPTION       = 'Fee on Instant Refunds';
    const REFUND_LTE_1K_DESCRIPTION         = 'Fee on Instant Refund <= INR 1,000';
    const REFUND_GT_1K_LTE_10K_DESCRIPTION  = 'Fee on Instant Refund > INR 1,000 & <= INR 10,000';
    const REFUND_GT_10K_DESCRIPTION         = 'Fee on Instant Refund > INR 10,000';

    protected static $typeToDescriptionMap = [
        self::CARD_LTE_2K            => self::CARD_LTE_2K_DESCRIPTION,
        self::CARD_GT_2K             => self::CARD_GT_2K_DESCRIPTION,
        self::OTHERS                 => self::OTHERS_DESCRIPTION,
        self::VALIDATION             => self::VALIDATION_DESCRIPTION,
        self::PRICING_BUNDLE         => self::PRICING_BUNDLE_DESCRIPTION,
        self::RX_TRANSACTIONS        => self::RX_TRANSACTIONS_DESCRIPTION,
        self::RX_ADJUSTMENTS         => self::RX_ADJUSTMENTS_DESCRIPTION,
        self::INSTANT_REFUNDS        => self::INSTANT_REFUNDS_DESCRIPTION,
        // This is kept to support older invoice
        self::NON_CARD               => self::OTHERS_DESCRIPTION,
        // This is kept to support older invoice
        self::REFUND_LTE_1K          => self::REFUND_LTE_1K_DESCRIPTION,
        // This is kept to support older invoice
        self::REFUND_GT_1K_LTE_10K   => self::REFUND_GT_1K_LTE_10K_DESCRIPTION,
        // This is kept to support older invoice
        self::REFUND_GT_10K          => self::REFUND_GT_10K_DESCRIPTION,
    ];

    // This are the row items for which we are charging tax from the merchant
    // For CARD_LTE_2K we do not charge from the merchant
    public static $taxablePrimaryCommissionTypes = [
        self::CARD_GT_2K,
        self::INSTANT_REFUNDS,
        self::OTHERS,
        self::VALIDATION,
        self::PRICING_BUNDLE,
    ];

    public static function getAllPrimaryBalanceTypes(): array
    {
        return [
            self::CARD_LTE_2K,
            self::CARD_GT_2K,
            self::INSTANT_REFUNDS,
            self::OTHERS,
            self::VALIDATION,
            self::PRICING_BUNDLE,
        ];
    }

    public static function getAllBankingBalanceTypes(): array
    {
        return [
            self::RX_TRANSACTIONS,
            self::RX_ADJUSTMENTS,
        ];
    }

    public static function isValid($type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function getGstSacCodeForType(string $type): string
    {
        return self::$typeToSacMap[$type] ?? self::DEFAULT_GST_SAC_CODE;
    }

    public static function getDescriptionFromType(string $type): string
    {
        return self::$typeToDescriptionMap[$type] ?? self::DEFAULT_DESCRIPTION;
    }

    public static function getTypeFromDescription(string $description): string
    {
        $descriptionToTypeMap = array_keys(self::$typeToDescriptionMap, $description, true);

        return empty($descriptionToTypeMap) === true ? "" : $descriptionToTypeMap[0];
    }
}
