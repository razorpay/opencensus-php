<?php

namespace RZP\Models\Dispute\DebitNote;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants as RZPConstants;

/**
 * @property Merchant\Entity $merchant
 * @property Dispute\Entity $disputes
 */
class Entity extends Base\PublicEntity
{


    const BASE_AMOUNT = 'base_amount';
    const ADMIN_ID    = 'admin_id';


    //constants
    const DEBIT_NOTE_ID = 'debit_note_id';

    //relations
    const TICKET = 'ticket';

    protected $fillable = [
        self::MERCHANT_ID,
        self::BASE_AMOUNT,
        self::ADMIN_ID,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::BASE_AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $entity = RZPConstants\Entity::DEBIT_NOTE;

    protected $generateIdOnCreate = true;


    /** related models */
    public function ticket()
    {
        return $this->hasOneThrough(
            'RZP\Models\Merchant\FreshdeskTicket\Entity',
            'RZP\Models\Dispute\DebitNote\Detail\Entity',
            'detail_id',
            'id',
            'id',
            'detail_id');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function disputes()
    {
        return $this->hasManyThrough(
            'RZP\Models\Dispute\Entity',
            'RZP\Models\Dispute\DebitNote\Detail\Entity',
            'debit_note_id',
            'id',
            'id', 'detail_id');
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }
}