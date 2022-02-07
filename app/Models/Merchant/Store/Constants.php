<?php


namespace RZP\Models\Merchant\Store;


class Constants
{
    const NAMESPACE = 'namespace';

    const STORE = 'store';

    const REDIS          = 'redis';
    const ELASTIC_SEARCH = 'elastic_search';
    const READ           = 'read';
    const WRITE          = 'write';

    const TTL                                                    = 'ttl';
    const GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS  = 108800;
    const GST_DETAILS_FROM_PAN_TTL_IN_SECONDS                    = 43200;
    const BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT_TTL_IN_SECONDS = 129600;
    const STORE_MERCHANT_DETAILS_TTL_IN_SECONDS                  = 217600;

    const PUBLIC                                                 = 'public';
    const INTERNAL                                               = 'internal';
}
