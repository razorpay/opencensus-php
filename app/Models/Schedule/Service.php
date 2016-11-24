<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Service extends Base\Service
{
    public function createSchedule($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_CREATE_REQUEST, $input);

        $schedule = (new Core)->createSchedule($input);

        $this->trace->info(TraceCode::SCHEDULE_CREATED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function getScheduleById($id)
    {
        $schedule = $this->repo->schedule->findByIdAndMerchantId($id, Account::SHARED_ACCOUNT);

        return $schedule->toArrayPublic();
    }

    public function deleteSchedule($id)
    {
        $this->trace->info(TraceCode::SCHEDULE_DELETE_REQUEST, ['schedule_id' => $id]);

        $merchantsUsingSchedule = $this->repo->merchant->fetchBySettlementScheduleId($id);

        if (count($merchantsUsingSchedule) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULE_IN_USE,
                $id);
        }

        $schedule = $this->repo->schedule->findOrFailPublic($id);

        $this->repo->schedule->deleteOrFail($schedule);

        $this->trace->info(TraceCode::SCHEDULE_DELETED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }

    public function getAllSchedules($input)
    {
        $schedules = $this->repo->schedule->fetch($input);

        return $schedules->toArrayPublic();
    }

    public function editSchedule($id, $input)
    {
        $this->trace->info(TraceCode::SCHEDULE_EDIT_REQUEST, $input);

        $schedule = $this->repo->schedule->findByIdAndMerchantId($id, Account::SHARED_ACCOUNT);

        $schedule = (new Core)->editSchedule($schedule, $input);

        $this->trace->info(TraceCode::SCHEDULE_EDITED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }
}
