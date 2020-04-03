<?php


namespace RZP\Models\BankTransferHistory;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const BANK_TRANSFER_ID                      = 'bank_transfer_id';
    const PAYER_NAME                            = 'payer_name';
    const PAYER_ACCOUNT                         = 'payer_account';
    const PAYER_IFSC                            = 'payer_ifsc';
    const PAYER_BANK_ACCOUNT_ID                 = 'payer_bank_account_id';
    const CREATED_BY                            = 'created_by';

    protected static $sign = 'bth';

    protected $entity = Constants\Entity::BANK_TRANSFER_HISTORY;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::BANK_TRANSFER_ID,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYER_BANK_ACCOUNT_ID,
        self::CREATED_BY,
    ];


    // ------------------------- Associations -------------------------

    public function payerBankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    public function bankTransfer()
    {
        return $this->belongsTo('RZP\Models\BankTransfer\Entity');
    }
}
