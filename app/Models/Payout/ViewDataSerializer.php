<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Payout\Entity as PayoutEntity;

class ViewDataSerializer extends Base\Core
{
    const DATE_FORMAT = 'M d, Y (h:i A)';

    protected static $epochs =[
        Entity::CREATED_AT,
    ];

    /**
     * @var array
     */
    protected $payout;

    public function __construct(array $payout)
    {
        parent::__construct();

        $this->payout = $payout;
    }

    public function serializePayoutForPublic(): array
    {
        $serialized = $this->payout;

        $serialized[PayoutEntity::ID] = PayoutEntity::getSignedId($serialized[PayoutEntity::ID]);

        $this->addFormattedEpochAttributesForPayout($serialized);

        return $serialized;
    }

    protected function addFormattedEpochAttributesForPayout(array & $serialized)
    {
        foreach (self::$epochs as $key)
        {
            $value = $serialized[$key] ?? null;

            $formatted = ($value === null) ? null : Carbon::createFromTimestamp($value , Timezone::IST)->format(self::DATE_FORMAT);

            $serialized[$key . '_formatted'] = $formatted;
        }
    }

}
