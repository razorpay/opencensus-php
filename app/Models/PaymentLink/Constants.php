<?php

namespace RZP\Models\PaymentLink;

class Constants
{
    // Input/output keys when doing ufh service requests.
    const RELATIVE_LOCATION        = 'relative_location';
    // Used as file type in ufh for images uplaoded in payment's page description.
    const PAYMENT_LINK_DESCRIPTION = 'payment_link_description';

    const PAYMENT_LINK = 'payment_link';

    // payment page V3 experiment
    const PAYMENT_PAGE_V3 = 'paymentpages_v3';

    const PARTNER_ZAPIER = 'partner_zapier';

    const PARTNER_SHIPROCKET = 'partner_shiprocket';

    const PARTNER_WEBHOOK_SETTINGS_KEY = 'partner_webhook_settings';

    // Hypertrace names
    const HT_PP_HOSTED_SLUG_DATA = 'payment_pages.hosted.pages.slug.get.data';
    const HT_PP_HOSTED_FIND         = 'payment_page.hosted.find';
    const HT_PP_HOSTED_GET_PAYLOAD  = 'payment_page.hosted.get.payload';
    const HT_PP_HOSTED_GET_TEMPLATE = 'payment_page.hosted.get.template';
    const HT_PP_HOSTED_VALIDATE     = 'payment_page.hosted.validate';
    const HT_PP_HOSTED_SERIALIZE    = 'payment_page.hosted.pages.serialize';
    const HT_PP_HOSTED_SCHEMA       = 'payment_page.hosted.pages.get.schema';

    const HT_PP_HOSTED_CACHE_DISPATCH   = 'payment_page.hosted.cache';
    const HT_PPI_TRANSACTION            = 'payment_page.ppi.update.transaction';
    const HT_PPI_UPDATE_LOCK            = 'payment_page.ppi.update.lock_and_reload';
    const HT_PPI_UPDATE_CORE            = 'payment_page.ppi.update.core';
    const HT_PPI_UPDATE_STATUS          = 'payment_pages.ppi.update.change_status';
    const HT_PPI_UPDATE_SAVE            = 'payment_pages.ppi.update.save_or_fail';

    const HT_PP_NOCODE_CUSTOM_URL_UPSERT    = 'payment_pages.ncu.upsert';

    const HT_PH_CREATE_REQUEST_PRECREATE               = 'payment_handle.create_request.precreate';
    const HT_PH_CREATE_REQUEST_CREATE                  = 'payment_handle.create_request.create';
    const HT_PH_GET                                    = 'payment_handle.get';
    const HT_PH_UPSERT_MERCHANT_SETTINGS               = 'payment_handle.upsert_merchant_settings';
    const HT_PH_GET_UNIQUE_HANDLE                      = 'payment_handle.create_request.precreate.get_unique_handle';
    const HT_PH_SHORTEN                                = 'payment_handle.shorten';
    const HT_PH_GIMLI_UPDATE                           = 'payment_handle.gimli_entry_update';
    const HT_PH_CREATE_REQUEST_CREATE_PP               = 'payment_handle.create_request.create.create_pp';
    const HT_PH_CREATE_REQUEST_CREATE_PP_TRANSACTION   = 'payment_handle.create_request.create.create_pp.transaction';
    const HT_PH_UPDATE                                 = 'payment_handle.update';

    // cache keys
    const SLUG_CACHE_KEY = 'SLUG_ENTITY_MAP';

    const DEFAULT_IMAGE_RESIZE_WIDTH = 760;

    const DEFAULT_IMAGE_COMPRESSION_QUALITY = 75;

    const SKIP_IMAGE_COMPRESSION_FORMAT = ['gif', 'GIF', 'webp', 'WEBP'];
}
