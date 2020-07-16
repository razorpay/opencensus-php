<?php

namespace RZP\Models\PayoutDowntime;

use RZP\Models\Base;

class Service extends Base\Service
{

    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator();
    }

    public function createPayoutDowntime(array $input): array
    {
        $this->validator->validateInput('create', $input[Entity::PAYOUT_DOWNTIME]);

        return $this->core()->create($input[Entity::PAYOUT_DOWNTIME]);
    }

    public function editPayoutDowntime($id, array $input): array
    {
        $this->validator->validateInput('edit', $input[Entity::PAYOUT_DOWNTIME]);

        return $this->core()->edit($id, $input[Entity::PAYOUT_DOWNTIME]);
    }

    public function fetchPayoutDowntime($id): array
    {
        $downtime = $this->core()->fetch($id);

        return $downtime->toArrayAdmin();
    }

    public function fetchPayoutDowntimeEnabled(): array
    {
        return $this->core()->fetchAllEnabledDowntimes($this->merchant);
    }

    public function fetchPayoutDowntimes($input): array
    {
        $downtime = $this->core()->fetchAllDowntimes($input);

        return $downtime->toArrayAdmin();
    }

}
