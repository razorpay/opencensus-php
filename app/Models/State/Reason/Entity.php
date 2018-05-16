<?php

namespace RZP\Models\State\Reason;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Merchant\Detail\RejectionReasons;

class Entity extends Base\PublicEntity
{
    const STATE_ID           = 'state_id';
    const REASON_TYPE        = 'reason_type';
    const REASON_CATEGORY    = 'reason_category';
    const REASON_CODE        = 'reason_code';
    const CREATED_AT         = 'created_at';
    const UPDATED_AT         = 'updated_at';

    const REASON_DESCRIPTION = 'reason_description';

    protected $entity = 'state_reason';

    protected $fillable = [
        self::REASON_TYPE,
        self::REASON_CATEGORY,
        self::REASON_CODE,
    ];

    protected $visible = [
        self::ID,
        self::STATE_ID,
        self::REASON_TYPE,
        self::REASON_CATEGORY,
        self::REASON_CODE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::STATE_ID,
        self::REASON_TYPE,
        self::REASON_CATEGORY,
        self::REASON_CODE,
        self::REASON_DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::REASON_DESCRIPTION,
    ];

    protected function setPublicReasonDescriptionAttribute(array & $array)
    {
        if ($array[self::REASON_TYPE] !== ReasonType::REJECTION)
        {
            return;
        }

        $reasonCodesDescriptionsMapping = RejectionReasons::REASON_CODES_DESCRIPTIONS_MAPPING;

        if (empty($reasonCodesDescriptionsMapping[$array[self::REASON_CODE]]) === false)
        {
            $array[self::REASON_DESCRIPTION] = $reasonCodesDescriptionsMapping[$array[self::REASON_CODE]];
        }
    }

    public function state()
    {
        return $this->belongsTo(State\Entity::class);
    }
}
