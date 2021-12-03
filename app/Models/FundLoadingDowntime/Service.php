<?php

namespace RZP\Models\FundLoadingDowntime;

class Service extends \RZP\Models\Base\Service
{
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator();

        $this->core = new Core();
    }

    public function createFundLoadingDowntime(array $input): array
    {
        $downtime = $this->core()->create($input);

        return $downtime->toArrayAdmin();
    }

    public function updateFundLoadingDowntime($id, $input): array
    {
        $downtime = $this->core()->update($id, $input);

        return $downtime->toArrayAdmin();
    }

    public function listFundLoadingDowntimes($input) : array
    {
        $downtime = $this->core()->listAllDowntimes($input);

        return $downtime->toArrayAdmin();
    }

    public function fetchFundLoadingDowntime($id)
    {
        $downtime = $this->core()->fetch($id);

        return $downtime->toArrayAdmin();
    }

    public function listActiveFundLoadingDowntimes($input)
    {
        $downtime = $this->core()->listActiveDowntimes($input);

        return $downtime->toArrayAdmin();
    }

    public function deleteFundLoadingDowntime($id)
    {
        $downtimeId = Entity::verifyIdAndSilentlyStripSign($id);

        $entity = $this->repo->fund_loading_downtimes->findOrFailPublic($downtimeId);

        $this->core()->delete($entity);

        return $entity->toArrayDeleted();
    }
}
