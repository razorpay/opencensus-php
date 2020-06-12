<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const GATEWAY                   = 'gateway';
    const IS_CREATED                = 'is_created';
    const ERROR_MESSAGE             = 'error_message';
    const NPCI_REFERENCE_ID         = 'npci_reference_id';
    const MODE                      = 'mode';
    const PAYEE_VPA                 = 'payee_vpa';
    const PAYER_VPA                 = 'payer_vpa';
    const PAYER_BANK                = 'payer_bank';
    const PAYER_ACCOUNT             = 'payer_account';
    const PAYER_IFSC                = 'payer_ifsc';
    const AMOUNT                    = 'amount';
    const GATEWAY_MERCHANT_ID       = 'gateway_merchant_id';
    const PROVIDER_REFERENCE_ID     = 'provider_reference_id';
    const TRANSACTION_REFERENCE     = 'transaction_reference';
    const TRANSACTION_TIME          = 'transaction_time';
    const REQUEST_PAYLOAD           = 'request_payload';

    protected static $sign = 'utr';

    protected $entity = Constants\Entity::UPI_TRANSFER_REQUEST;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;
}
