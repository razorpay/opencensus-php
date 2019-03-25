<?php

namespace RZP\Error\P2p;

class PublicErrorDescription extends \RZP\Error\PublicErrorDescription
{
    // @codingStandardsIgnoreStart

    const MAP = [
         ErrorCode::SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED                => 'SERVER_ERROR_CONTEXT_MERCHANT_REQUIRED',
         ErrorCode::SERVER_ERROR_CONTEXT_DEVICE_REQUIRED                  => 'SERVER_ERROR_CONTEXT_DEVICE_REQUIRED',
         ErrorCode::SERVER_ERROR_CONTEXT_HANDLE_REQUIRED                  => 'SERVER_ERROR_CONTEXT_HANDLE_REQUIRED',

         ErrorCode::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE             => 'Device is not registered for the given handle',
         ErrorCode::BAD_REQUEST_MERCHANT_NOT_ALLOWED_ON_HANDLE            => 'Merchant is not allowed to use the handle',
         ErrorCode::BAD_REQUEST_DEVICE_DOES_NOT_BELONG_TO_MERCHANT        => 'Device is not registered for the given merchant',
         ErrorCode::BAD_REQUEST_INVALID_MERCHANT_IN_CONTEXT               => 'Invalid merchant set in context',

         ErrorCode::GATEWAY_ERROR_DEVICE_INVALID_TOKEN                    => 'Token is invalid or expired',
    ];

    // @codingStandardsIgnoreEnd
}
