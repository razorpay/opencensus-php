<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Service extends Base\Service
{
    public function create(string $accountId, array $input)
    {
        (new Validator)->validateInput('create_stakeholder', $input);

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = $this->core()->create($accountId, $input);

        $dimensions = $this->getStakeholderMetricDimensions();

        $publicResponse = (new Response)->createResponse($stakeholder);

        $this->trace->count(Metric::STAKEHOLDER_V2_CREATE_SUCCESS_TOTAL, $dimensions);

        return $publicResponse;
    }

    public function fetch(string $accountId, string $id)
    {
        $timeStarted = millitime();

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($id);
        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = $this->core()->fetch($accountId, $id);

        $publicResponse = (new Response)->createResponse($stakeholder);

        $dimensions = $this->getStakeholderMetricDimensions();

        $this->trace->count(Metric::STAKEHOLDER_V2_FETCH_SUCCESS_TOTAL, $dimensions);

        $this->trace->histogram(Metric::STAKEHOLDER_V2_FETCH_TIME_IN_MS, millitime() - $timeStarted, $dimensions);

        return $publicResponse;
    }

    public function fetchAll(string $accountId)
    {
        $timeStarted = microtime(true);

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholders = $this->core()->fetchAll($accountId);

        $dimensions = $this->getStakeholderMetricDimensions();

        $publicResponse = (new Response)->createListResponse($stakeholders);

        $this->trace->count(Metric::STAKEHOLDER_V2_FETCH_ALL_SUCCESS_TOTAL, $dimensions);

        $this->trace->histogram(Metric::STAKEHOLDER_V2_FETCH_ALL_TIME_IN_MS, get_diff_in_millisecond($timeStarted), $dimensions);

        return $publicResponse;
    }

    public function update(string $accountId, string $id, array $input)
    {
        (new Validator)->validateInput('edit_stakeholder', $input);

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($id);
        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = $this->core()->update($accountId, $id, $input);

        $publicResponse = (new Response)->createResponse($stakeholder);

        $dimensions = $this->getStakeholderMetricDimensions();

        $this->trace->count(Metric::STAKEHOLDER_V2_UPDATE_SUCCESS_TOTAL, $dimensions);

        return $publicResponse;
    }

    private function getStakeholderMetricDimensions(): array
    {
        $dimensions = [
            'partner_type'   => $this->merchant->getPartnerType()
        ];

        return $dimensions;
    }
}
