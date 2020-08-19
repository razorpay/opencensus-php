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

    protected $fillable = [
        self::GATEWAY,
        self::NPCI_REFERENCE_ID,
        self::PAYEE_VPA,
        self::PAYER_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::GATEWAY_MERCHANT_ID,
        self::PROVIDER_REFERENCE_ID,
        self::TRANSACTION_REFERENCE,
        self::TRANSACTION_TIME,
        self::REQUEST_PAYLOAD,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::IS_CREATED,
        self::ERROR_MESSAGE,
        self::NPCI_REFERENCE_ID,
        self::PAYEE_VPA,
        self::PAYER_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::GATEWAY_MERCHANT_ID,
        self::PROVIDER_REFERENCE_ID,
        self::TRANSACTION_REFERENCE,
        self::TRANSACTION_TIME,
        self::REQUEST_PAYLOAD,
    ];

    protected $casts = [
        self::IS_CREATED    => 'bool',
        self::AMOUNT        => 'int',
    ];

    public function setGateway(string $gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setNpciReferenceId(string $npciReferenceId)
    {
        $this->setAttribute(self::NPCI_REFERENCE_ID, $npciReferenceId);
    }

    public function setRequestPayload($requestPayload)
    {
        $this->setAttribute(self::REQUEST_PAYLOAD, $requestPayload);
    }
}
