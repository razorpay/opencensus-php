<?php


namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;

class CommunicationHandler extends Handler
{
    public function execute(string $merchantId, Entity $action, array $params = [])
    {
        $escalation = $this->repo->merchant_onboarding_escalations->findOrFailPublic($action->getEscalationId());

        $args = [
            'merchant'  => $this->repo->merchant->findOrFailPublic($merchantId),
            'params'    => [
                'threshold' => $escalation->getThresholdInRupee()
            ]
        ];

        (new OnboardingNotificationHandler($args))->sendForEvent($params['event']);
    }

}
