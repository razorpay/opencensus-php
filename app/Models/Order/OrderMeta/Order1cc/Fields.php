<?php

namespace RZP\Models\Order\OrderMeta\Order1cc;

class Fields
{
    const ID                                = 'id';
    const ORDER_ID                          = 'order_id';
    // Storing various fields in JSON format instead of as columns as relations at this level are not required.
    const DATA                              = 'data'; //json
    const LINE_ITEMS                        = 'line_items';
    const COD_FEE                           = 'cod_fee';
    const SUB_TOTAL                         = 'sub_total';
    const LINE_ITEMS_TOTAL                  = 'line_items_total';
    const NET_PRICE                         = 'net_price';
    const SHIPPING_FEE                      = 'shipping_fee';
    const CUSTOMER_DETAILS                  = 'customer_details';
    const COD_INTELLIGENCE                  = 'cod_intelligence';
    const PROMOTIONS                        = 'promotions';
    // Line Item fields
    const LINE_ITEM_TYPE                    = 'type';
    const LINE_ITEM_SKU                     = 'sku';
    const LINE_ITEM_VARIANT_ID              = 'variant_id';
    const LINE_ITEM_OTHER_PRODUCT_CODES     = 'other_product_codes';
    const LINE_ITEM_PRICE                   = 'price';
    const LINE_ITEM_OFFER_PRICE             = 'offer_price';
    const LINE_ITEM_TAX_AMOUNT              = 'tax_amount';
    const LINE_ITEM_QUANTITY                = 'quantity';
    const LINE_ITEM_NAME                    = 'name';
    const LINE_ITEM_DESCRIPTION             = 'description';
    const LINE_ITEM_WEIGHT                  = 'weight';
    const LINE_ITEM_DIMENSIONS              = 'dimensions';
    const LINE_ITEM_IMAGE_URL               = 'image_url';
    const LINE_ITEM_PRODUCT_URL             = 'product_url';
    const LINE_ITEM_NOTES                   = 'notes';
    // Line Item Dimension fields
    const LINE_ITEM_DIMENSIONS_LENGTH       = 'length';
    const LINE_ITEM_DIMENSIONS_WIDTH        = 'width';
    const LINE_ITEM_DIMENSIONS_HEIGHT       = 'height';
    const PROMOTIONS_REFERENCE_ID           = 'reference_id';
    const PROMOTIONS_TYPE                   = 'type';
    const PROMOTIONS_CODE                   = 'code';
    const PROMOTIONS_VALUE                  = 'value';
    const PROMOTIONS_VALUE_TYPE             = 'value_type';
    const PROMOTIONS_DESCRIPTION            = 'description';
    //Customer Details Fields
    const CUSTOMER_DETAILS_ID               = 'id';
    const CUSTOMER_DETAILS_NAME             = 'name';
    const CUSTOMER_DETAILS_CONTACT          = 'contact';
    const CUSTOMER_DETAILS_EMAIL            = 'email';
    const CUSTOMER_DETAILS_SHIPPING_ADDRESS = 'shipping_address';
    const CUSTOMER_DETAILS_BILLING_ADDRESS  = 'billing_address';
    const CUSTOMER_DETAILS_DEVICE           = 'device';
    const CUSTOMER_DETAILS_DEVICE_ID        = 'id';
    //Cod Intelligence Fields
    const COD_INTELLIGENCE_ENABLED = 'enabled';
    const COD_ELIGIBLE = 'cod_eligible';

    const ONE_CLICK_CHECKOUT = 'one_click_checkout';

    public static $dataFields = [
        self::LINE_ITEMS,
        self::COD_FEE,
        self::SUB_TOTAL,
        self::LINE_ITEMS_TOTAL,
        self::NET_PRICE,
        self::SHIPPING_FEE,
        self::CUSTOMER_DETAILS,
        self::PROMOTIONS,
    ];
}
