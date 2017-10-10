<?php

namespace RZP\Models\Adjustment;

use RZP\Exception;
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

    public function addFeesAdjustment($input)
    {
        if (isset($input[Entity::MERCHANT_ID]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Merchant ID not passed!');
        }

        $merchantId = $input[Entity::MERCHANT_ID];

        unset($input[Entity::MERCHANT_ID]);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $adj = (new Adjustment\Core)->createFeesAdjustment($input, $merchant);

        $this->logActionToSlack($merchant, SlackActions::ADD_ADJUSTMENT, $input);

        return $adj->toArrayPublic();
    }

    /**
     * Adds Multiple adjustments
     * @param array $input [list of adjustments to be added]
     */
    public function addMultipleAdjustment(array $input)
    {
        $merchantToAmountAdjList = $input['adjustments'];

        unset($input['adjustments']);

        $success = 0;
        $total = 0;
        $failed = 0;
        $failedIds = [];

        foreach ($merchantToAmountAdjList as $merchantId => $amount)
        {
            try
            {
                $input[Entity::MERCHANT_ID] = $merchantId;

                $input[Entity::AMOUNT] = $amount;

                $this->addAdjustment($input);

                $success++;

                $total += $amount;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    null,
                    [
                        Entity::MERCHANT_ID => $merchantId,
                        Entity::AMOUNT      => $amount
                    ]);

                $failed++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'success'    => $success,
            'total'      => $total/100,
            'failed'     => $failed,
            'failed_Ids' => $failedIds
        ];

        return $response;
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

    public function splitAdjustments(array $input): array
    {
        return $this->core()->splitAdjustments($input);
    }
}
