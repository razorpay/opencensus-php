<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;

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

    public function editSchedule($id, $input)
    {
        $this->trace->info(TraceCode::SCHEDULE_EDIT_REQUEST, $input);

        $schedule = $this->repo->schedule->findByIdAndMerchantId($id, Account::SHARED_ACCOUNT);

        $schedule = (new Schedule\Core)->editSchedule($schedule, $input);

        $this->trace->info(TraceCode::SCHEDULE_EDITED, $schedule->toArray());

        return $schedule->toArrayPublic();
    }
}
