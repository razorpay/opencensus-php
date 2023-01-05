<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;
use App;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Constants as MConstants;
use RZP\Models\Merchant\AutoKyc\Escalations\Utils;
use RZP\Models\Merchant\AutoKyc\Escalations\Entity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Escalations as NewEscalation;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;

abstract class BaseEscalationType
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app[MConstants::REPO];
    }

    public abstract function triggerEscalation($merchants, $merchantsGmvList, string $type, int $level);

    public function createEscalationsV1($merchants, string $type, int $level, $escalationMethod)
    {
        foreach ($merchants as $merchant)
        {
            $this->createEscalationV1ForMerchant($merchant, $type, $level, $escalationMethod);
        }
    }

    public function createEscalationV1ForMerchant($merchant, string $type, int $level, $escalationMethod)
    {
        try
        {
            $escalation = (new Entity)->build([
                                                  Entity::MERCHANT_ID       => $merchant->getId(),
                                                  Entity::ESCALATION_TYPE   => $type,
                                                  Entity::ESCALATION_METHOD => $escalationMethod,
                                                  Entity::ESCALATION_LEVEL  => $level,
                                              ]);
            $this->repo->merchant_auto_kyc_escalations->saveOrFail($escalation);

            $this->app[MConstants::TRACE]->info(TraceCode::SELF_SERVE_ESCALATION_SUCCESS, [
                'type'        => $type,
                'level'       => $level,
                'merchant_id' => $merchant->getId()
            ]);
        }
        catch (\Exception $e)
        {
            $this->app[MConstants::TRACE]->info(TraceCode::SELF_SERVE_ESCALATION_FAILURE, [
                'type'        => $type,
                'level'       => $level,
                'reason'      => 'something went wrong while handling escalation',
                'trace'       => $e->getMessage(),
                'merchant_id' => $merchant->getId()
            ]);
        }
    }

    public function createEscalationV2ForMerchant($merchant, $amount, string $type, int $level)
    {
        $milestone = Utils::getEscalationMilestone($type, $level);

        if (empty($milestone) === false)
        {
            $threshold = ($type == Constants::SOFT_LIMIT) ? env(Constants::SOFT_LIMIT_MCC_PENDING_THRESHOLD) : env(Constants::HARD_LIMIT_MCC_PENDING_THRESHOLD);

            $merchantId = $merchant->getId();

            $merchantDetails = $this->repo->merchant_detail->getByMerchantId($merchantId);

            $escalationConfig = (new NewEscalation\Core)->getEscalationConfigForThresholdAndMilestone($merchantDetails, $threshold, $milestone);

            if (empty($escalationConfig) === false)
            {
                if ((new NewEscalation\Handler)->canTriggerEscalation($merchantDetails, $escalationConfig) === true)
                {
                    (new NewEscalation\Handler)->triggerEscalation($merchantId,
                                                                   $amount,
                                                                   $threshold,
                                                                   $escalationConfig,
                                                                   NewEscalation\Constants::PAYMENT_BREACH);

                    $this->app[MConstants::TRACE]->info(TraceCode::SELF_SERVE_ESCALATION_SUCCESS, [
                        'type'        => $type,
                        'level'       => $level,
                        'merchant_id' => $merchant->getId(),
                        'mileStone'   => $milestone,
                        'threshold'   => $threshold
                    ]);
                }
            }

        }

    }
}
