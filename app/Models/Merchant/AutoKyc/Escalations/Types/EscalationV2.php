<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\AutoKyc\Escalations\Utils;
use RZP\Models\Merchant\Escalations as NewEscalation;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;

class EscalationV2 extends BaseEscalationType
{

    public function send($merchants, $merchantsGmvList, string $type, int $level)
    {

        $merchantsGmvMap = collect($merchantsGmvList)->mapToDictionary(function($item, $key) {
            return [$item[DetailEntity::MERCHANT_ID] => $item['total']];
        });
        foreach ($merchants as $merchant)
        {
            try
            {
                $this->saveEscalationForMerchantToV2($merchant, $merchantsGmvMap[$merchant->getId()][0], $type, $level);
                $this->app['trace']->info(TraceCode::ESCALATION_V2_SUCCESS, [
                    'type'        => $type,
                    'level'       => $level,
                    'merchant_id' => $merchant->getId()
                ]);
            }
            catch (\Exception $e)
            {
                $this->app['trace']->info(TraceCode::ESCALATION_V2_FAILURE, [
                    'type'        => $type,
                    'level'       => $level,
                    'reason'      => 'something went wrong while handling v2 escalation',
                    'trace'       => $e->getMessage(),
                    'merchant_id' => $merchant->getId()
                ]);
            }
        }
    }

    public function saveEscalationForMerchantToV2($merchant, $amount, string $type, int $level)
    {
        $milestone = Utils::getEscalationMilestone($type, $level);
        if (empty($milestone) === false)
        {
            $threshold = ($type == Constants::SOFT_LIMIT) ? env(Constants::SOFT_LIMIT_MCC_PENDING_THRESHOLD) : env(Constants::HARD_LIMIT_MCC_PENDING_THRESHOLD);

            $merchantId          = $merchant->getId();
            $merchantDetails     = $this->repo->merchant_detail->getByMerchantId($merchantId);
            $isExperimentEnabled = (new MerchantCore())->isRazorxExperimentEnable($merchantId,
                                                                                  RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY);
            if ($isExperimentEnabled===true)
            {
                $escalationConfig = (new NewEscalation\Core)->getEscalationConfigForThresholdAndMilestone($merchantDetails, $threshold, $milestone);

                if (empty($escalationConfig) === false)
                {
                    (new NewEscalation\Handler)->triggerEscalation(
                        $merchantId, $amount, $threshold, $escalationConfig, NewEscalation\Constants::PAYMENT_BREACH
                    );
                    $this->app['trace']->info(TraceCode::SELF_SERVE_ESCALATION_SUCCESS, [
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

    public function triggerEscalation($merchants, $merchantsGmvList, string $type, int $level)
    {
        $this->createEscalationsV1($merchants, $type, $level, Constants::EMAIL);

        $this->send($merchants, $merchantsGmvList, $type, $level);
    }
}
