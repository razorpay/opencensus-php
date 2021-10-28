<?php

namespace RZP\Models\Store;

use Carbon\Carbon;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Settings;
use RZP\Models\Merchant;
use RZP\Models\LineItem;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CURRENCY        => 'filled|string|currency',
        Entity::TITLE           => 'required|string|min:3|max:40',
        Entity::DESCRIPTION     => 'string|max:65535|nullable|utf8', // 65535 bytes is size of mysql's text data type.
        Entity::SLUG            => 'required|min:4|max:30|custom',
    ];

    protected static $editRules = [
        Entity::TITLE           => 'required|string|min:3|max:40',
        Entity::DESCRIPTION     => 'string|max:65535|nullable|utf8', // 65535 bytes is size of mysql's text data type.
        Entity::SLUG            => 'required|min:4|max:30|custom',
        Entity::SETTINGS        => 'nullable|array|custom',
        Entity::SETTINGS . '.' . Entity::SHIPPING_FEES                        => 'sometimes|string|mysql_unsigned_int|min:100',
        Entity::SETTINGS . '.' . Entity::SHIPPING_DAYS                        => 'sometimes|string|Integer|between:1,90',
        Entity::SETTINGS . '.' . Entity::PP_FB_PIXEL_TRACKING_ID              => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_GA_PIXEL_TRACKING_ID              => 'string|max:32',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_ADD_TO_CART_ENABLED      => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_INITIATE_PAYMENT_ENABLED => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_PAYMENT_COMPLETE         => 'nullable|string|in:0,1',
        Entity::SETTINGS . '.' . Entity::PP_FB_EVENT_PAYMENT_COMPLETE         => 'nullable|string|in:0,1',

    ];

    protected static $addProductRules = [
        Entity::PRODUCT_NAME             => 'required|string|min:3|max:40',
        Entity::PRODUCT_DESCRIPTION      => 'sometimes|string|max:65535|utf8',
        Entity::PRODUCT_IMAGES           => 'nullable|array|custom',
        Entity::PRODUCT_STOCK            => 'required|mysql_unsigned_int|min:1',
        Entity::PRODUCT_DISCOUNTED_PRICE => 'nullable|mysql_unsigned_int|min:100',
        Entity::PRODUCT_SELLING_PRICE    => 'required|mysql_unsigned_int|min:100',
    ];

    protected static $editProductRules = [
        Entity::PRODUCT_NAME             => 'required|string|min:3|max:40',
        Entity::PRODUCT_DESCRIPTION      => 'sometimes|string|max:65535|utf8',
        Entity::PRODUCT_IMAGES           => 'nullable|array|custom',
        Entity::PRODUCT_STOCK            => 'required|mysql_unsigned_int|min:1',
        Entity::PRODUCT_DISCOUNTED_PRICE => 'sometimes|mysql_unsigned_int|min:100',
        Entity::PRODUCT_SELLING_PRICE    => 'required|mysql_unsigned_int|min:100',
    ];

    protected static $patchProductRules = [
        Entity::STATUS                => 'required|string|in:active,inactive',
    ];

    protected static $uploadImagesRules = [
        'images'     => 'required|array|min:1|max:5',
        'images.*'   => 'required|mimes:jpg,jpeg,png,gif,bmp,svg|max:2048',
    ];

    protected static $createOrderRules = [
        Entity::LINE_ITEMS  => 'required|array|custom|min:1|max:25',
        Entity::NOTES       => 'sometimes|notes',
    ];

    protected static $createOrderLineItemRules = [
        Entity::PAYMENT_PAGE_ITEM_ID => 'required|public_id',
        LineItem\Entity::QUANTITY    => 'sometimes|integer|min:1',
    ];

    public function validateImages(string $attribute, $values)
    {
        if (is_array($values) === false)
        {
            throw new BadRequestValidationFailureException(
                'Images must be an array.',
                Entity::PRODUCT_IMAGES);
        }

        if (empty($values) === true)
        {
            return;
        }

        foreach ($values as $url)
        {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new BadRequestValidationFailureException(
                    'improper image url',
                    Entity::PRODUCT_IMAGES,
                    compact('url'));
            }
        }
    }

    public function validateStoreExistsForMerchant(Merchant\Entity $merchant)
    {
        $storeExistsSetting = Settings\Accessor::for($merchant, Settings\Module::PAYMENT_STORE)->all();

        if (!empty($storeExistsSetting) === true && !empty($storeExistsSetting['store_id']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Store already exists for this merchant',
                null,
                null);
        }
    }

    public function validateSlug(string $attribute, string $value)
    {
        $valid = preg_match('/^[A-Za-z0-9-_]+$/', $value);

        if ($valid !== 1)
        {
            throw new BadRequestValidationFailureException(
                'slug must only contain alpha numeric, _ and - characters',
                Entity::SLUG,
                compact('value'));
        }
    }

    public function validateSettings(string $attribute, $values)
    {
        if (is_array($values) === false)
        {
            throw new BadRequestValidationFailureException(
                'Settings must be an array.',
                Entity::SETTINGS);
        }

        if (empty($values) === true)
        {
            return;
        }

        $extraSettingsKeys = array_values(array_diff(array_keys($values), Entity::SETTINGS_KEYS));
        if (empty($extraSettingsKeys) === false)
        {
            throw new BadRequestValidationFailureException(
                'Extra settings keys must not be sent - ' . implode(', ', $extraSettingsKeys) . '.',
                Entity::SETTINGS);
        }
    }

    public function validateDiscountAndSellingPrice(array $input)
    {
        if (isset($input[Entity::PRODUCT_DISCOUNTED_PRICE]) === false)
        {
            return;
        }

        $sellingPrice = (int)$input[Entity::PRODUCT_SELLING_PRICE];

        $discountedPrice = $input[Entity::PRODUCT_DISCOUNTED_PRICE];

        if(($discountedPrice > $sellingPrice) === true)
        {
            throw new BadRequestValidationFailureException(
                'Discounted price cannot be greater than selling price',
                Entity::PRODUCT_SELLING_PRICE);
        }
    }

    public function validateStock(array $input, PaymentPageItem\Entity $product)
    {
        $newStock = $input[Entity::PRODUCT_STOCK];

        $productSold = $product->getQuantitySold();

        if (($newStock < $productSold) === true)
        {
            throw new BadRequestValidationFailureException(
                'Total Stock cannot be less than stock sold',
                Entity::PRODUCT_STOCK);
        }
    }

    public function validateStoreIsActiveStore(Entity $store, Entity $activeStore)
    {
        if ($store->getPublicId() !== $activeStore->getPublicId())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'This store does not exist');
        }
    }

    public function validateLineItems(string $attribute, $value)
    {
        if(is_array($value) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                null,
                null,
                'line items must be array');
        }

        if(empty($value) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                null,
                null,
                'Please select an amount to pay.');
        }
        // TODO Add amount sql & constraint validation
    }
}
