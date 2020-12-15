<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    protected $entity = 'stakeholder';

    const ID_LENGTH = 14;

    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const EMAIL                     = 'email';
    const NAME                      = 'name'; // this will store the name ass per the promoter pan
    const PHONE_PRIMARY             = 'phone_primary';
    const PHONE_SECONDARY           = 'phone_secondary';
    const DIRECTOR                  = 'director';
    const EXECUTIVE                 = 'executive';
    const PERCENTAGE_OWNERSHIP      = 'percentage_ownership';
    const NOTES                     = 'notes';
    const POI_IDENTIFICATION_NUMBER = 'poi_identification_number';
    const POI_STATUS                = 'poi_status';
    const POA_STATUS                = 'poa_status';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::EMAIL,
        self::NAME,
        self::PHONE_PRIMARY,
        self::PHONE_SECONDARY,
        self::DIRECTOR,
        self::EXECUTIVE,
        self::PERCENTAGE_OWNERSHIP,
        self::NOTES,
        self::POI_IDENTIFICATION_NUMBER,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::EMAIL,
        self::NAME,
        self::PHONE_PRIMARY,
        self::PHONE_SECONDARY,
        self::DIRECTOR,
        self::EXECUTIVE,
        self::PERCENTAGE_OWNERSHIP,
        self::NOTES,
        self::POI_IDENTIFICATION_NUMBER,
        self::POI_STATUS,
        self::POA_STATUS,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID, 'id');
    }
}
