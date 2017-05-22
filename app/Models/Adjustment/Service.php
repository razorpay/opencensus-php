<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\SlackActions as SlackActions;

class Service extends Base\Service
{
    use NotifyTrait;

    public function getAdjustment($id)
    {
        $adj = $this->repo->adjustment->findByPublicIdAndMerchant($id, $this->merchant);

        return $adj->toArrayPublic();
    }

    public function getAdjustments($input)
    {
        $adjustments = $this->repo->adjustment->fetch($input, $this->merchant->getKey());

        return $adjustments->toArrayPublic();
    }

    public function addAdjustment($input)
    {
        $merchantId = $input[Entity::MERCHANT_ID];
        unset($input[Entity::MERCHANT_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $adj = (new Adjustment\Core)->createAdjustment($input, $merchant);

        $this->logActionToSlack($merchant, SlackActions::ADD_ADJUSTMENT, $input);

        return $adj->toArrayPublic();
    }

    public function postReverseAdjustments($input)
    {
        $adjustmentIds = $input['ids'];

        $success = 0;
        $failed = 0;
        $failedIds = [];

        foreach ($adjustmentIds as $adjustmentId)
        {
            $adjustment = null;

            try
            {
                Adjustment\Entity::verifyIdAndStripSign($adjustmentId);

                $adjustment = $this->repo->adjustment->findOrFail($adjustmentId);

                $request = [
                    Entity::AMOUNT      => -1 * $adjustment->getAmount(),
                    Entity::CURRENCY    => 'INR',
                    Entity::DESCRIPTION => 'Reverse adjustment for '. $adjustment->getId()
                ];

                $revAdj = (new Adjustment\Core)->createAdjustment($request, $adjustment->merchant);

                $success++;
            }
            catch (\Exception $ex)
            {
                $failed++;

                $failedIds[] = $adjustment->getId();
            }

            $response['success'] = $success;
            $response['failed'] = $failed;
            $response['failedIds'] = $failedIds;
        }

        return $response;
    }
}
