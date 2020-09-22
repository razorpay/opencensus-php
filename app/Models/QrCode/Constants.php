<?php

namespace RZP\Models\QrCode;

class Constants
{
    const QR_CODE_FILE_NAME = 'QrCode.jpeg';
    // TODO: Find out what should
    // be the best size
    const QR_CODE_WIDTH     = 220;
    const QR_CODE_HEIGHT    = 300;

    const QR_STRING_MPAN_TOKENIZATION_SUCCESS_COUNT = 'qr_string_mpan_tokenization_success_count';
    const QR_STRING_MPAN_TOKENIZATION_FAILED_COUNT  = 'qr_string_mpan_tokenization_failed_count';
    const QR_STRING_MPAN_TOKENIZATION_SUCCESS_IDS   = 'qr_string_mpan_tokenization_success_ids';
    const QR_STRING_MPAN_TOKENIZATION_FAILED_IDS    = 'qr_string_mpan_tokenization_failed_ids';
}
