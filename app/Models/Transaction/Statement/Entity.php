<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\BankTransfer;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement
 */
class Entity extends Base\PublicEntity
{

    const ID         = 'id';
    const AMOUNT     = 'amount';
    const BALANCE    = 'balance';
    const BALANCE_ID = 'balance_id';
    const CREDIT     = 'credit';
    const DEBIT      = 'debit';
    const SOURCE     = 'source';
    const CUSTOMER   = 'customer';
    const TYPE       = 'type';
    const UTR        = 'UTR';
    const SOURCE_ID  = 'source_id';
    const ENTITY     = 'entity';

    protected $entity = 'statement';

    protected static $sign = 'stmt';

    protected $embeddedRelations = [
        self::SOURCE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SOURCE_ID,
        self::UTR,
        self::AMOUNT,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::SOURCE,
        self::CUSTOMER,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::UTR,
        self::SOURCE_ID,
        self::SOURCE,
        self::ENTITY,
    ];

    public function source()
    {
        return $this->morphTo('source', 'type', 'entity_id');
    }

// ----------------------- Getters --------------------------------------------

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

// ----------------------- Getters Ends--------------------------------------------

// ----------------------- Public setters--------------------------------------------

    public function setPublicSourceAttribute(array & $array)
    {
        if ($this->getType() === Constants\Entity::PAYOUT)
        {
            unset($array['source'][Payout\Entity::AMOUNT]);
            unset($array['source'][Payout\Entity::FEES]);
            unset($array['source'][Payout\Entity::TAX]);
            unset($array['source'][Payout\Entity::UTR]);
            unset($array['source'][Payout\Entity::STATUS]);
            unset($array['source'][Payout\Entity::SETTLED_ON]);
            unset($array['source'][Payout\Entity::CREATED_AT]);
            unset($array['source'][Payout\Entity::UPDATED_AT]);

            $array['source']['customer'] = $this->source->customer->toArrayPublic();
            $array['source']['account']  = $this->source->destination->toArrayPublic();

            return;
        }

        if ($this->getType() === Constants\Entity::BANK_TRANSFER)
        {
            unset($array['source'][BankTransfer\Entity::PAYMENT_ID]);
            unset($array['source'][BankTransfer\Entity::VIRTUAL_ACCOUNT_ID]);
            $array['source'][BankTransfer\Entity::PAYER_NAME] = $this->source->getPayerName();
            $array['source'][BankTransfer\Entity::PAYER_ACCOUNT] = $this->source->getPayerAccount();
            $array['source'][BankTransfer\Entity::PAYER_IFSC] = $this->source->getPayerIfsc();
            return;
        }

        unset($array['source']);
    }

    public function setPublicUtrAttribute(array & $array)
    {
        $array[self::UTR] = $this->source->getUtr();
    }

    public function setPublicSourceIdAttribute(array & $array)
    {
        $array[self::SOURCE_ID] = $this->source->getPublicId();
    }

// ----------------------- Public setters end --------------------------------------------
}
