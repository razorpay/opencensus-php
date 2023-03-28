<?php

namespace RZP\Models\QrCode;

use RZP\Models\FileStore;

class Constants
{
    const QR_CODE_FILE_NAME = 'QrCode.jpeg';
    const QR_CODE           = 'qr_code';
    const QR_CODE_TEMP_IMAGE_URL = 'https://rzp.io/i/xwlv5oK';

    // TODO: Find out what should
    // be the best size
    const QR_CODE_WIDTH         = 210;
    const QR_CODE_HEIGHT        = 210;
    const QR_CODE_SIZE          = 210;
    const UPI_QR_CODE_WIDTH     = 210;
    const UPI_QR_CODE_HEIGHT    = 210;
    const UPI_QR_CODE_SIZE      = 210;

    const UPI_QR_DEST_X         = 115;
    const UPI_QR_DEST_Y         = 195;
    const QR_DEST_X             = 37;
    const QR_DEST_Y             = 245;
    const SORCE_X               = 0;
    const SORCE_Y               = 0;
    const MARGIN                = 0;
    const QR_EMAIL_X            = 302;
    const QR_EMAIL_Y            = 36;
    const QR_EMAIL_WIDTH        = 140;
    const QR_EMAIL_HEIGHT       = 140;

    const OPACITY               = 100;

    const QR_STRING_MPAN_TOKENIZATION_SUCCESS_COUNT = 'qr_string_mpan_tokenization_success_count';
    const QR_STRING_MPAN_TOKENIZATION_FAILED_COUNT  = 'qr_string_mpan_tokenization_failed_count';
    const QR_STRING_MPAN_TOKENIZATION_SUCCESS_IDS   = 'qr_string_mpan_tokenization_success_ids';
    const QR_STRING_MPAN_TOKENIZATION_FAILED_IDS    = 'qr_string_mpan_tokenization_failed_ids';

    // Qr Code Tag Values constants
    const MERCHANT_CATEGORY = 'merchant_category';
    const MERCHANT_NAME     = 'merchant_name';
    const MERCHANT_CITY     = 'merchant_city';
    const MERCHANT_PINCODE  = 'merchant_pincode';

    // Qr Code Extension
    const QR_CODE_EXTENSION = FileStore\Format::JPEG;

    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

    const QR_V2_UPI_QR_CODE_SIZE   = 380;
    const QR_V2_UPI_QR_CODE_WIDTH  = 380;
    const QR_V2_UPI_QR_CODE_HEIGHT = 380;

    const QR_V2_UPI_QR_DEST_X      = 146;
    const QR_V2_UPI_QR_DEST_Y      = 658;

    const QR_V2_UPI_QR_NAME_YPOS     = 1350;
    const QR_V2_BHARAT_QR_NAME_YPOS  = 280;

    const QR_CODE_V2_TR_SUFFIX = 'qrv2';

    const DUMMY_QR_CODE_VPA = 'qrrazorpay@dummy';

    const REMINDER_BASE_URL     = 'reminders/send';
    const REMINDER_NAMESPACE    = 'qr_code';
    const REMINDER_ENTITY_NAME  = 'qr_code';

    const UTC_INDIA_OFFSET = '+05:30';

    const QR_V2_VERSION         = '01';
    const QR_V2_MODE_STATIC     = '01';
    const QR_V2_MODE_DYNAMIC    = '15';
    const QR_V2_QR_MEDIUM       = '04';

    // in seconds
    const NO_ORDER_CHECKOUT_QR_DEFAULT_EXPIRY_WINDOW = 15 * 60;

    const MAX_RETRY_ATTEMPTS_FOR_QR_CODE_URL_SHORTEN_GIMLI_FAILURES = 3;

    const REQUEST_SOURCE              = 'X-Razorpay-Request-Source';
}
