<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants as EscalationConstant;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\EscalationV2 as Escalation;

class EscalationHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $milestone = Constants::HARD_LIMIT_LEVEL_4;

        (new Escalation())->createEscalationV1ForMerchant($merchant, Constants::MILESTONE_MAPPING[$milestone][Constants::TYPE], Constants::MILESTONE_MAPPING[$milestone][Constants::LEVEL], Constants::EMAIL);
    }
}
