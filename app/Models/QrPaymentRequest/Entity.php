<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';

    const QR_CODE_ID            = 'qr_code_id';

    /*
     *  Contains unique payment reference
     * - provider_reference_id in case of bharat_qr
     * - npci_txn_id in case of upi_qr type
     */
    const TRANSACTION_REFERENCE = 'transaction_reference';

    const EXPECTED              = 'expected';

    const REQUEST_SOURCE        = 'request_source';

    // This refers to bharat_qr table's id
    const BHARAT_QR_ID          = 'bharat_qr_id';

    // This refer's to upi table's id
    const UPI_ID                = 'upi_id';

    // All the payment failures including unexpected
    // reason will be stored in this field
    const FAILURE_REASON        = 'failure_reason';

    const IS_CREATED            = 'is_created';

    const REQUEST_PAYLOAD       = 'request_payload';

    protected static $sign = 'qpr';

    protected $entity = 'qr_payment_request';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::QR_CODE_ID,
        self::TRANSACTION_REFERENCE,
        self::EXPECTED,
        self::FAILURE_REASON,
        self::CREATED_AT,
        self::BHARAT_QR_ID,
        self::UPI_ID,
        self::REQUEST_SOURCE,
        self::REQUEST_PAYLOAD,
        self::IS_CREATED,
    ];

    protected $visible = [
        self::ID,
        self::QR_CODE_ID,
        self::TRANSACTION_REFERENCE,
        self::EXPECTED,
        self::FAILURE_REASON,
        self::CREATED_AT,
        self::UPI_ID,
        self::BHARAT_QR_ID,
        self::REQUEST_SOURCE,
        self::REQUEST_PAYLOAD,
        self::IS_CREATED,
    ];
}
