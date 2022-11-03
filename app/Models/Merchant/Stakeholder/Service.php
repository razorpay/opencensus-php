<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Constants\HyperTrace;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Trace\Tracer;
use RZP\Models\Merchant\AccountV2\Type;

class Service extends Base\Service
{
    public function create(string $accountId, array $input)
    {
        (new Validator)->validateInput('create_stakeholder', $input);

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = Tracer::inspan(['name' => HyperTrace::CREATE_STAKEHOLDER_V2_CORE], function () use ($accountId, $input) {

            return $this->core()->create($accountId, $input);
        });

        $dimensions = $this->getStakeholderMetricDimensions();

        $publicResponse = Tracer::inspan(['name' => HyperTrace::STAKEHOLDER_CREATE_RESPONSE], function () use ($stakeholder) {

            return (new Response)->createResponse($stakeholder);
        });

        $this->trace->count(Metric::STAKEHOLDER_V2_CREATE_SUCCESS_TOTAL, $dimensions);

        return $publicResponse;
    }

    public function fetch(string $accountId, string $id)
    {
        $timeStarted = millitime();

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($id);
        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = Tracer::inspan(['name' => HyperTrace::FETCH_STAKEHOLDER_V2_CORE], function () use ($accountId, $id) {

            return $this->core()->fetch($accountId, $id);
        });

        $publicResponse = Tracer::inspan(['name' => HyperTrace::STAKEHOLDER_CREATE_RESPONSE], function () use ($stakeholder) {

            return (new Response)->createResponse($stakeholder);
        });

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

        $stakeholders = Tracer::inspan(['name' => HyperTrace::FETCH_ALL_STAKEHOLDER_V2_CORE], function () use ($accountId) {

            return $this->core()->fetchAll($accountId);
        });

        $dimensions = $this->getStakeholderMetricDimensions();

        $publicResponse = Tracer::inspan(['name' => HyperTrace::STAKEHOLDER_CREATE_RESPONSE], function () use ($stakeholders) {

            return (new Response)->createListResponse($stakeholders);
        });

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

        $stakeholder = Tracer::inspan(['name' => HyperTrace::UPDATE_STAKEHOLDER_V2_CORE], function () use ($accountId, $id, $input) {

            return $this->core()->update($accountId, $id, $input);
        });

        $publicResponse = Tracer::inspan(['name' => HyperTrace::STAKEHOLDER_CREATE_RESPONSE], function () use ($stakeholder) {

            return (new Response)->createResponse($stakeholder);
        });

        $dimensions = $this->getStakeholderMetricDimensions();

        $this->trace->count(Metric::STAKEHOLDER_V2_UPDATE_SUCCESS_TOTAL, $dimensions);

        return $publicResponse;
    }

    private function getStakeholderMetricDimensions(): array
    {
        $dimensions = [
            'partner_type'   => $this->merchant->getPartnerType() ?? Type::ROUTE
        ];

        return $dimensions;
    }
}
