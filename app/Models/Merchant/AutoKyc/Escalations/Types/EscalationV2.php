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
use RZP\Models\Merchant\Constants as MConstants;

class EscalationV2 extends BaseEscalationType
{

    public function triggerEscalation($merchants, $merchantsGmvList, string $type, int $level)
    {

        $merchantsGmvMap = collect($merchantsGmvList)->mapToDictionary(function($item, $key) {
            return [$item[DetailEntity::MERCHANT_ID] => $item[MConstants::TOTAL]];
        });
        foreach ($merchants as $merchant)
        {
            try
            {
                $this->createEscalationV1ForMerchant($merchant, $type, $level, Constants::EMAIL);

                $this->createEscalationV2ForMerchant($merchant, $merchantsGmvMap[$merchant->getId()][0], $type, $level);

                $this->app[MConstants::TRACE]->info(TraceCode::ESCALATION_V2_SUCCESS, [
                    'type'        => $type,
                    'level'       => $level,
                    'merchant_id' => $merchant->getId()
                ]);
            }
            catch (\Exception $e)
            {
                $this->app[MConstants::TRACE]->info(TraceCode::ESCALATION_V2_FAILURE, [
                    'type'        => $type,
                    'level'       => $level,
                    'reason'      => 'something went wrong while handling v2 escalation',
                    'trace'       => $e->getMessage(),
                    'merchant_id' => $merchant->getId()
                ]);
            }
        }
    }
}
