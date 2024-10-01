<?php

namespace RZP\Services\Route;

final class Constant
{
    const DIRECT_TRANSFER_ENDPOINT = '/v1/transfers/direct';
    const TRANSFER_FETCH_BY_ID_ENDPOINT = '/v1/transfers/api/%s';
    const PAYMENT_FETCH_ENDPOINT = '/v1/payments';
    const SAVE_API_PAYMENT_ENDPOINT = '/v1/payments/%s';
    const SAVE_API_TRANSFER_ENDPOINT = '/v1/transfers/api/%s';
}
