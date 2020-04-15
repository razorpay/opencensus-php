<?php

namespace RZP\Models\Adjustment;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\Adjustment\Entity as AdjustmentEntity;

class ViewDataSerializer extends Base\Core
{
    const DATE_FORMAT = 'M d, Y (h:i A)';

    protected static $epochs =[
        Entity::CREATED_AT,
    ];

    /**
     * @var array
     */
    protected $adjustment;

    public function __construct(array $adjustment)
    {
        parent::__construct();

        $this->adjustment = $adjustment;
    }

    public function serializeAdjustmentForPublic(): array
    {
        $serialized = $this->adjustment;

        $serialized[AdjustmentEntity::ID] = AdjustmentEntity::getSignedId($serialized[AdjustmentEntity::ID]);

        $this->addFormattedEpochAttributesForAdjustment($serialized);

        return $serialized;
    }

    protected function addFormattedEpochAttributesForAdjustment(array & $serialized)
    {
        foreach (self::$epochs as $key)
        {
            $value = $serialized[$key] ?? null;

            $formatted = ($value === null) ? null : Carbon::createFromTimestamp($value , Timezone::IST)->format(self::DATE_FORMAT);

            $serialized[$key . '_formatted'] = $formatted;
        }
    }

}
