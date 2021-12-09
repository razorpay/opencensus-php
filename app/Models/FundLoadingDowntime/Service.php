<?php

namespace RZP\Models\FundLoadingDowntime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;

class Service extends Base\Service
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

    public function listFundLoadingDowntimes($input): array
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

    public function notificationFlow(string $flowType, array $input)
    {
        $this->trace->info(TraceCode::FUND_LOADING_DOWNTIME_NOTIFICATION_REQUEST,
                           [
                               "input"     => $input,
                               "flow_type" => $flowType
                           ]
        );

        switch ($flowType)
        {
            case Constants::CREATION:

                $this->validator->validateInput(Validator::CREATION_FLOW, $input);

                $response = $this->core->createMultipleDowntimesAndNotify($input, $flowType);
                break;

            case Constants::RESOLUTION:
            case Constants::UPDATION:

                $this->validator->validateInput(Validator::UPDATION_FLOW, $input);

                $response = $this->core->updateMultipleDowntimesAndNotify($input, $flowType);
                break;

            case Constants::CANCELLATION:

                $this->validator->validateInput(Validator::CANCELLATION_FLOW, $input);

                $response = $this->core->deleteMultipleDowntimesAndNotify($input, $flowType);
                break;

            default:
                throw new LogicException('Flow type not defined for ' . $flowType);
        }

        return $response;
    }
}
