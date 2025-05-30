<?php

namespace RZP\Services\Route;

final class Constant
{
    const DIRECT_TRANSFER_ENDPOINT = '/v1/transfers/direct';
    const PAYMENT_TRANSFER_ENDPOINT = '/v1/transfers/payments/%s';
    const TRANSFER_REVERSAL_ENDPOINT = '/v1/transfers/%s/reversals';
    const TRANSFER_FETCH_BY_ID_ENDPOINT = '/v1/transfers/api/%s';
    const REVERSAL_FETCH_BY_ID_ENDPOINT = '/v1/reversals/api/%s';
    const PATCH_TRANSFER_ROUTE_ENDPOINT = '/v1/transfers/%s';
    const PAYMENT_FETCH_ENDPOINT = '/v1/payments';
    const SAVE_API_PAYMENT_ENDPOINT = '/v1/payments/%s';
    const SAVE_API_TRANSFER_ENDPOINT = '/v1/transfers/api/%s';
    const UPDATE_FEATURE_ENDPOINT = '/v1/features/update';
    const TRANSFER_PAYMENT_FETCH_ENDPOINT = '/v1/source_payments/%s';
    const TRANSFER_FETCH_MULTIPLE_INTERNAL_ENDPOINT = '/v1/internal/transfers';
    const SAVE_API_TRANSFER_PAYMENT_ENDPOINT = '/v1/source_payments/%s';
    const DECREMENT_TRANSFER_PAYMENT_ENDPOINT = '/v1/source_payments/%s/decrement';
}
