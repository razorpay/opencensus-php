<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Models\Payment\Refund;
use RZP\Models\Settlement\Status as SettlementStatus;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;

class Service extends Base\Service
{
    public function initiateFundTransfers(array $input, $channel = null)
    {
        $this->trace->info(TraceCode::INITIATE_FUND_TRANSFER, $input);

        $data = (new Initiator)->initiateFundTransfers($input, $channel);

        return $data;
    }

    public function reconcileFundTransfers(array $input, string $channel): array
    {
        $this->trace->info(TraceCode::FTA_BULK_RECONCILE_REQUEST, $input);

        $summary = (new BulkRecon($input, $channel))->process();

        return $summary;
    }

    public function bulkUpdate(array $input)
    {
        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_BULK_UPDATE_REQUEST,
            $input);

        $ids = array_keys($input);

        Entity::verifyIdAndSilentlyStripSignMultiple($ids);

        $fundTransferAttempts = $this->repo
                                     ->fund_transfer_attempt
                                     ->findManyWithRelations($ids, ['source']);

        $updatedIds = [];

        $notUpdatedIds = [];

        foreach ($fundTransferAttempts as $fundTransferAttempt)
        {
            $sourceId = null;

            $id = $fundTransferAttempt->getId();

            $params = $input[$id];

            (new Validator)->validateInput('edit', $params);

            $fundTransferAttempt->fill($params);

            $this->repo->saveOrFail($fundTransferAttempt);

            if ($fundTransferAttempt->isBatchSameAsSource() === true)
            {
                $sourceId = $this->updateSource($fundTransferAttempt->source, $params);
            }

            if ($sourceId !== null)
            {
                $updatedIds[] = $id;
            }
            else
            {
                $notUpdatedIds[] = $id;
            }
        }

        $response = [
            'updated_ids'       => $updatedIds,
            'not_updated_ids'   => $notUpdatedIds
        ];

        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_UPDATED,
            $response);

        return $response;
    }

    /**
     * Updated fund transafer source with the request param
     *
     * @param Base\Entity $source
     * @param array $params
     * @return null|string
     */
    protected function updateSource(Base\Entity $source, array $params)
    {
        $status = false;

        foreach ($params as $key => $value)
        {
            if ($key === Entity::STATUS)
            {
                $value  = $this->getSourceStatus($source, $value);
            }

            $status |= $this->setSourceAttribute($source, $key, $value);
        }
        if ((bool) $status === true)
        {
            $this->repo->saveOrFail($source);

            return $source->getId();
        }

        return null;
    }

    protected function getSourceStatus(Base\Entity $source, string $value)
    {
        if ($value !== AttemptStatus::INITIATED)
        {
            return $value;
        }

        switch (true)
        {
            case $source instanceof Settlement\Entity:

                return Settlement\Status::CREATED;

            case $source instanceof Refund\Entity:

                return Refund\Status::CREATED;

            default:

                return $value;
        }
    }

    /**
     * Sets the attribute value of property if available in source
     *
     * @param Base\Entity $source
     * @param string $key
     * @param string $value
     * @return bool
     */
    protected function setSourceAttribute(Base\Entity $source, string $key, string $value): bool
    {
        $method = 'set' . studly_case($key);

        // If will check for the setter method for the property and if exist it'll update it.
        // It is dont because some source entity has mocked the setter method.
        // So rather then checking for attribute, we are checking for setter method.
        if (method_exists($source, $method) === true)
        {
            $source->{$method}($value);

            return true;
        }

        return false;
    }
}
