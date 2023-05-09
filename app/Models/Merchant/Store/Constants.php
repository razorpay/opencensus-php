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
    const DELETE         = 'delete';

    const TTL                                                    = 'ttl';
    const VISIBILITY_TTL_IN_SECONDS                              = 5184000;
    const REFERRAL_TTL_IN_SECONDS                                = 7776000;
    const GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS  = 108800;
    const GET_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS  = 108800;
    const GST_DETAILS_FROM_PAN_TTL_IN_SECONDS                    = 43200;
    const BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT_TTL_IN_SECONDS = 7776000;
    const STORE_MERCHANT_DETAILS_TTL_IN_SECONDS                  = 217600;
    const BVS_SUGGESTED_NAMES_TTL_IN_SECONDS                     = 217600;
    const MTU_POPUP_TTL_IN_SECONDS                               = 7776000;
    const FTUX_POPUP_TTL_IN_SECONDS                              = 2592000;
    const UPI_TERMINAL_BANNER_TTL_IN_SECONDS                     = 2592000;

    const PUBLIC                                                 = 'public';
    const INTERNAL                                               = 'internal';
}
