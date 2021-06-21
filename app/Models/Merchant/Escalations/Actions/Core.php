<?php


namespace RZP\Models\Merchant\Escalations\Actions;

use RZP\Jobs\MerchantEscalationAction;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Merchant\Escalations\Utils;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function fetchActionById(string $actionId): Entity
    {
        return $this->repo->onboarding_escalation_actions->findOrFailPublic($actionId);
    }

    public function create(string $merchantId, string $escalationId, string $handlerClazz, array $params = [])
    {
        $actionEntity = (new Entity)->build([
            Entity::MERCHANT_ID     => $merchantId,
            Entity::ACTION_HANDLER  => Utils::getClassShortName($handlerClazz),
            Entity::ESCALATION_ID   => $escalationId,
            Entity::STATUS          => Constants::PENDING
        ]);

        $this->repo->onboarding_escalation_actions->saveOrFail($actionEntity);

        if($this->shouldExecuteInAsync() === true)
        {
            // dispatch action to execute in async
            MerchantEscalationAction::dispatch($merchantId, $actionEntity->getId(), $handlerClazz, $params);
        }
        else
        {
            $this->handleAction($merchantId, $actionEntity->getId(), $handlerClazz, $params);
        }
    }

    public function handleAction(string $merchantId, string $actionId, string $handlerClazz, array $params = [])
    {
        $action = $this->fetchActionById($actionId);

        if($this->isValidHandler($handlerClazz) === false)
        {
            $this->trace->debug(TraceCode::ESCALATION_ACTION_JOB_ERROR, [
                'merchant_id'   => $merchantId,
                'action_id'     => $actionId,
                'reason'        => 'invalid handler class: ' . $handlerClazz
            ]);

            return;
        }

        $handlerInstanze = new $handlerClazz();

        $handlerInstanze->handleAction($merchantId, $action, $params);
    }

    private function shouldExecuteInAsync()
    {
        return (bool) ConfigKey::get(ConfigKey::ASYNC_ESCALATION_HANDLING_ENABLED, false);
    }

    private function isValidHandler($handlerClazz)
    {
        if(empty($handlerClazz) === true)
        {
            return false;
        }

        if(class_exists($handlerClazz) === false)
        {
            return false;
        }

        return is_a($handlerClazz, Handlers\Handler::class, true);
    }
}
