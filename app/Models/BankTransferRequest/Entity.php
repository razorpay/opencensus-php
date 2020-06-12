<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const GATEWAY           = 'gateway';
    const IS_CREATED        = 'is_created';
    const ERROR_MESSAGE     = 'error_message';
    const UTR               = 'utr';
    const MODE              = 'mode';
    const PAYEE_NAME        = 'payee_name';
    const PAYEE_ACCOUNT     = 'payee_account';
    const PAYEE_IFSC        = 'payee_ifsc';
    const PAYER_NAME        = 'payer_name';
    const PAYER_ACCOUNT     = 'payer_account';
    const PAYER_IFSC        = 'payer_ifsc';
    const AMOUNT            = 'amount';
    const DESCRIPTION       = 'description';
    const NARRATION         = 'narration';
    const TIME              = 'time';
    const REQUEST_PAYLOAD   = 'request_payload';

    protected static $sign = 'btr';

    protected $entity = Constants\Entity::BANK_TRANSFER_REQUEST;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;
}
