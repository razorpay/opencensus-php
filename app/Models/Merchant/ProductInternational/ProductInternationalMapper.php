<?php

namespace RZP\Models\Merchant\ProductInternational;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission\Name;

class ProductInternationalMapper
{
    // Status of the international enablement per product
    const ENABLED              = '1';
    const DISABLED             = '0';
    const REQUESTED_ENABLEMENT = '2';

    const VALID_STATUS_TRANSITION = [
        self::DISABLED             => [self::REQUESTED_ENABLEMENT, self::ENABLED],
        self::ENABLED              => [self::DISABLED],
        self::REQUESTED_ENABLEMENT => [self::DISABLED, self::ENABLED]];

    const VALID_STATUSES = [self::ENABLED, self::DISABLED, self::REQUESTED_ENABLEMENT];

    //Product Names
    const PAYMENT_GATEWAY = 'payment_gateway';
    const PAYMENT_LINKS   = 'payment_links';
    const PAYMENT_PAGES   = 'payment_pages';
    const INVOICES        = 'invoices';
    const PRODUCTS_PA_CB  = 'products_pa_cb';

    //Product Categories
    const PG        = 'pg';
    const PROD_V2   = 'prod_v2';
    const PROD_PACB  = 'prod_pacb';

    const LIVE_PRODUCTS = [self::PAYMENT_GATEWAY, self::PAYMENT_LINKS, self::PAYMENT_PAGES, self::INVOICES, self::PRODUCTS_PA_CB];

    // Product Category Mapping
    const PRODUCT_CATEGORIES =
        [
            self::PG        => [self::PAYMENT_GATEWAY],
            self::PROD_V2   => [self::PAYMENT_LINKS, self::PAYMENT_PAGES, self::INVOICES],
            self::PROD_PACB => [self::PRODUCTS_PA_CB]
        ];

    //Position of products (default value of ProductInternational is 0000000000)
    const PRODUCT_POSITION =
        [
            self::PAYMENT_GATEWAY => 0,
            self::PAYMENT_LINKS   => 1,
            self::PAYMENT_PAGES   => 2,
            self::INVOICES        => 3,
            self::PRODUCTS_PA_CB   => 4,
        ];

    //Permisson of products
    const PRODUCT_PERMISSION =
        [
            self::PG      =>    Name::EDIT_MERCHANT_PG_INTERNATIONAL,
            self::PROD_V2 =>    Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
            self::PROD_PACB =>  Name::INTERNATIONAL_PRODUCTS_PA_CB_ENABLEMENT
        ];

    const PRODUCT_PERMISSIONS_LIST =
        [
            Name::EDIT_MERCHANT_PG_INTERNATIONAL,
            Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
            Name::EDIT_MERCHANT_INTERNATIONAL_NEW,
            Name::TOGGLE_INTERNATIONAL_REVAMPED,
            Name::INTERNATIONAL_PRODUCTS_PA_CB_ENABLEMENT
        ];

    //Product ErrorCode mapping
    const PRODUCT_ERROR_CODE =
        [
            self::PAYMENT_LINKS   => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_LINKS,
            self::PAYMENT_PAGES   => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_PAGES,
            self::INVOICES        => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_INVOICES,
            self::PAYMENT_GATEWAY => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY,
            self::PRODUCTS_PA_CB  => ErrorCode::BAD_REQUEST_INTERNATIONAL_NOT_ALLOWED_FOR_PRODUCTS_PA_CB
        ];

    const PRODUCT_TO_PERMISSION_MAPPING = 
        [
            self::PAYMENT_LINKS   =>  Name::TOGGLE_INTERNATIONAL_REVAMPED,
            self::PAYMENT_PAGES   =>  Name::TOGGLE_INTERNATIONAL_REVAMPED,
            self::INVOICES        =>  Name::TOGGLE_INTERNATIONAL_REVAMPED,
            self::PAYMENT_GATEWAY =>  Name::TOGGLE_INTERNATIONAL_REVAMPED,
            self::PRODUCTS_PA_CB  =>  Name::INTERNATIONAL_PRODUCTS_PA_CB_ENABLEMENT,
        ];

    const INTERNATIONAL_PRODUCTS = 'international_products';

    const ADMIN_FLOW    = "admin_flow";
    const MERCHANT_FLOW = "merchant_flow";

    /**
     * @param string $productName
     *
     * @return string|null
     * @throws Exception\BadRequestException
     */
    public static function fetchProductCategory(string $productName): ?string
    {
        foreach (self::PRODUCT_CATEGORIES as $productCategory => $productNames)
        {

            if (in_array($productName, $productNames, true) === true)
            {
                return $productCategory;
            }

        }
        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME,
            null,
            ['data' => $productName]
        );
    }
}

