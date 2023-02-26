<?php

namespace RZP\Models\Merchant\InternationalEnablement\Detail;

class Constants
{
    const GOODS_TYPE_PHYSICAL         = 'physical_goods';
    const GOODS_TYPE_DIGITAL_SERVICES = 'digital_services';
    const GOODS_TYPE_BOTH             = 'both';

    const PAYMENT_GATEWAY = 'payment_gateway';
    const PAYMENT_LINKS   = 'payment_links';
    const PAYMENT_PAGES   = 'payment_pages';
    const INVOICES        = 'invoices';

    const ACTION_DRAFT  = 'draft';
    const ACTION_SUBMIT = 'submit';

    const ACCEPTS_INTL_TXNS = 'accepts_intl_txns';
    
    const MAX_VALUE_IDENTIFIER = -1;

    const GOODS_TYPE_OPTIONS = [
        self::GOODS_TYPE_PHYSICAL,
        self::GOODS_TYPE_DIGITAL_SERVICES,
        self::GOODS_TYPE_BOTH,
    ];

    const GOODS_TYPE_VALIDATOR_CSV = 
        self::GOODS_TYPE_PHYSICAL . ',' .
        self::GOODS_TYPE_DIGITAL_SERVICES . ',' .
        self::GOODS_TYPE_BOTH;

    const PRODUCTS_VALIDATOR_CSV =
        self::PAYMENT_GATEWAY . ',' .
        self::PAYMENT_LINKS . ',' .
        self::PAYMENT_PAGES . ',' .
        self::INVOICES;

    const VALIDATOR_CREATE_ACTION_KEY = 'create_for_%s';

    public static function getValidGoodsTypesCSV(): string
    {
        return implode(',', self::GOODS_TYPE_OPTIONS);
    }
}
