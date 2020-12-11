<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount;

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
    const REQUEST_SOURCE    = 'request_source';

    const TRANSACTION_ID        = 'transaction_id';
    const PAYER_ACCOUNT_TYPE    = 'payer_account_type';
    const PAYER_ADDRESS         = 'payer_address';
    const CURRENCY              = 'currency';
    const ATTEMPT               = 'attempt';

    const INTENDED_VIRTUAL_ACCOUNT_ID   = 'intended_virtual_account_id';
    const ACTUAL_VIRTUAL_ACCOUNT_ID     = 'actual_virtual_account_id';
    const BANK_TRANSFER_ID              = 'bank_transfer_id';
    const PAYMENT_ID                    = 'payment_id';
    const ORDER_ID                      = 'order_id';
    const PRODUCT_TYPE                  = 'product_type';
    const PRODUCT_ID                    = 'product_id';
    const MERCHANT_NAME                 = 'merchant_name';

    // Input keys
    const FIRST_TIME_ON_TEST_MODE = 'first_time_on_test_mode';

    protected static $sign = 'btr';

    protected $entity = Constants\Entity::BANK_TRANSFER_REQUEST;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::GATEWAY,
        self::UTR,
        self::MODE,
        self::PAYEE_NAME,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NARRATION,
        self::TIME,
        self::REQUEST_PAYLOAD,
        self::REQUEST_SOURCE,
        self::INTENDED_VIRTUAL_ACCOUNT_ID,
        self::ACTUAL_VIRTUAL_ACCOUNT_ID,
        self::BANK_TRANSFER_ID,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::PRODUCT_TYPE,
        self::PRODUCT_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::IS_CREATED,
        self::ERROR_MESSAGE,
        self::UTR,
        self::MODE,
        self::PAYEE_NAME,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NARRATION,
        self::TIME,
        self::INTENDED_VIRTUAL_ACCOUNT_ID,
        self::ACTUAL_VIRTUAL_ACCOUNT_ID,
        self::BANK_TRANSFER_ID,
        self::PAYMENT_ID,
        self::ORDER_ID,
        self::PRODUCT_TYPE,
        self::PRODUCT_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REQUEST_SOURCE,
    ];

    protected static $generators = [
        self::UTR,
    ];

    protected static $unsetCreateInput = [
        self::PAYER_ACCOUNT_TYPE,
        self::PAYER_ADDRESS,
        self::CURRENCY,
        self::ATTEMPT,
    ];

    protected $casts = [
        self::IS_CREATED    => 'bool',
        self::AMOUNT        => 'int',
    ];

    public function generateUtr($input)
    {
        $this->setAttribute(self::UTR, $input[self::TRANSACTION_ID]);
    }

    // -------------------- Getters --------------------

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getPayeeAccount()
    {
        return $this->getAttribute(self::PAYEE_ACCOUNT);
    }

    // -------------------- End Getters --------------------

    // -------------------- Setters --------------------

    public function setAmountAttribute(float $amount)
    {
        $amount = (int) number_format(($amount * 100), 0, '.', '');

        $this->attributes[self::AMOUNT] = $amount;
    }

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setUtr(string $utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setRequestPayload($requestPayload)
    {
        $this->setAttribute(self::REQUEST_PAYLOAD, $requestPayload);
    }

    public function setRequestSource($requestSource)
    {
        $this->setAttribute(self::REQUEST_SOURCE, $requestSource);
    }

    // -------------------- End Setters --------------------
}
