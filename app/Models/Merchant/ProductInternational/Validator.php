<?php

namespace RZP\Models\Merchant\ProductInternational;

use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator
{
    /**
     * @param string $productName
     *
     * @throws Exception\BadRequestException
     */
    public function validateProductName(string $productName)
    {
        if (key_exists($productName, ProductInternationalMapper::PRODUCT_POSITION) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME,
                null,
                ['product' => $productName]
            );
        }
    }

    /**
     * @param string $newStatus
     * @param string $currentStatus
     *
     * @throws Exception\BadRequestException
     */
    public function validateStatus(string $newStatus, string $currentStatus)
    {
        if (in_array($newStatus, ProductInternationalMapper::VALID_STATUSES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_INVALID_STATUS,
                null,
                ['status' => $newStatus]
            );
        }

        if (in_array(
            $newStatus,
            ProductInternationalMapper::VALID_STATUS_TRANSITION[$currentStatus],
            true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_STATUS_TRANSITION,
                null,
                ['current_status' => $currentStatus,
                 'new_status'     => $newStatus]
            );
        }
    }

}
