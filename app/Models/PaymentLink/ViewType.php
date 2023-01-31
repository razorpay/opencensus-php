<?php

namespace RZP\Models\PaymentLink;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * View Type can be button or page
 * page is the default hosted page presentation of payment page
 * button is the merchant integration on website
 * @package RZP\Models\PaymentLink
 */
class ViewType
{
    const BUTTON              = 'button';
    const PAGE                = 'page';
    const SUBSCRIPTION_BUTTON = 'subscription_button';
    const PAYMENT_HANDLE      = 'payment_handle';
    const FILE_UPLOAD_PAGE    = 'file_upload_page';

    public static function isValid(string $viewType): bool
    {
        $key = __CLASS__ . '::' . strtoupper($viewType);

        return ((defined($key) === true) and (constant($key) === $viewType));
    }

    public static function checkViewType(string $viewType)
    {
        if (self::isValid($viewType) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid view type: ' . $viewType);
        }
    }
}
