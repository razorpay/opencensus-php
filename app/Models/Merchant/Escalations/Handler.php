<?php


namespace RZP\Models\Merchant\Escalations;

use App;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use RZP\lib\ConditionParser\Parser;
use Illuminate\Foundation\Application;
use RZP\Jobs\MerchantEscalationAction;

class Handler
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];
    }

    private function getEscalatedMilestonesForThreshold($threshold, $existingEscalations)
    {
        $milestones = [];

        foreach ($existingEscalations as $escalation)
        {
            if($escalation[Entity::THRESHOLD] === $threshold)
            {
                $milestones[] = $escalation[Entity::MILESTONE];
            }
        }

        return $milestones;
    }

    private function getNextPossibleEscalation($breachedAmount, $existingEscalations, $merchantDetails)
    {
        $escalationMatrix = array_reverse(Constants::PAYMENTS_ESCALATION_MATRIX, true);

        /*
         * loop over all escalations and pick the one closest to breachedAmount and not yet triggered
         */
        foreach ($escalationMatrix as $threshold => $escalations)
        {
            if($breachedAmount < $threshold)
            {
                continue;
            }

            /*
             * get all existing escalated milestones for the given threshold
             */
            $existingMilestones = $this->getEscalatedMilestonesForThreshold($threshold, $existingEscalations);

            $possibleEscalations = array_filter($escalations, function ($escalation)
            use($existingMilestones) {
                return in_array($escalation[Constants::MILESTONE], $existingMilestones) === false;
            });

            if(empty($possibleEscalations) === false)
            {
                $escalationConfig = $possibleEscalations[0];

                if($this->canTriggerEscalation($merchantDetails, $escalationConfig) === true)
                {
                    return [$threshold, $escalationConfig];
                }
            }
        }

        return [null, null];
    }

    private function canTriggerEscalation($merchantDetails, $escalationConfig): bool
    {
        $conditions = $escalationConfig[Constants::CONDITIONS];

        return (new Parser)->parse($conditions, function ($key, $value) use ($merchantDetails){
            return $merchantDetails->getAttribute($key) === $value;
        });
    }

    public function triggerPaymentEscalation(string $merchantId, int $breachedAmount, array $existingEscalations)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        $merchantDetails = $merchant->merchantDetail;

        $experimentEnabled = (new Merchant\Core)->isRazorxExperimentEnable(
            $merchantDetails->getMerchantId(), RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY);

        if($experimentEnabled === false)
        {
            return [false, "experiment not enabled for merchant"];
        }

        [$breachedThreshold, $nextEscalation] = $this->getNextPossibleEscalation(
            $breachedAmount, $existingEscalations, $merchantDetails);

        if(empty($nextEscalation) === true)
        {
            return [false, "no next escalation found for amount ". $breachedAmount];
        }

        $isEnabled = $nextEscalation[Constants::ENABLE] ?? true;

        if($isEnabled === false)
        {
            return [false, "escalation not enabled for threshold ".$breachedThreshold];
        }

        $this->triggerEscalation(
            $merchantId, $breachedAmount, $breachedThreshold, $nextEscalation, Constants::PAYMENT_BREACH);

        return [true, null];
    }

    public function triggerEscalation(string $merchantId, $breachedAmount, $threshold, $escalationConfig, $type)
    {
        $escalationEntity = (new Entity)->build([
            Entity::MERCHANT_ID     => $merchantId,
            Entity::MILESTONE       => $escalationConfig[Constants::MILESTONE],
            Entity::ESCALATED_TO    => $escalationConfig[Constants::TO],
            Entity::THRESHOLD       => $threshold,
            Entity::AMOUNT          => $breachedAmount,
            Entity::TYPE            => $type,
            Entity::DESCRIPTION     => $escalationConfig[Constants::DESCRIPTION],
        ]);

        $this->repo->merchant_onboarding_escalations->saveOrFail($escalationEntity);

        $this->executeActions($merchantId, $escalationConfig, $escalationEntity);

        $this->trace->info(TraceCode::ESCALATION_ATTEMPT_SUCCESS, [
            'merchant_id'   => $merchantId,
            'type'          => $type,
            'escalation'    => $escalationEntity
        ]);
    }

    private function executeActions(string $merchantId, array $escalationConfig, $escalationEntity)
    {
        $actions = $escalationConfig[Constants::ACTIONS] ?? [];

        if(empty($actions) === true)
        {
            return;
        }

        foreach ($actions as $action)
        {
            $handlerClazz = $action[Constants::HANDLER];
            $params = $action[Constants::PARAMS] ?? [];

            (new Actions\Core)->create($merchantId, $escalationEntity->getId(), $handlerClazz, $params);
        }
    }
}
