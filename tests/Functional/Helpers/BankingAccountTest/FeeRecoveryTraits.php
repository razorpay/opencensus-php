<?php

namespace RZP\Tests\Functional\Helpers\BankingAccount;

use Carbon\Carbon;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\FeeRecovery;

trait FeeRecoveryTrait
{
    protected function setupDefaultScheduleForFeeRecovery()
    {
        $this->ba->adminAuth();

        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content' => [
                'type'      => Schedule\Type::FEE_RECOVERY,
                'name'      => 'Basic T+7',
                'period'    => 'daily',
                'interval'  => 7, // keeping it same as how we are fetching from DB while creating task
            ],
        ];

        $this->makeRequestAndGetContent($createScheduleRequest);
    }

    public function assertScheduleTasksForActivatedCA($balanceId, $merchantId, $timeBeforeActivation, $timeAfterActivation)
    {
        $now = Carbon::now(Timezone::IST);

        [$start, $end] = (new FeeRecovery\Core)->getNextInterval($now);

        $this->assertScheduleTasksForActivatedAccounts($balanceId, $merchantId, $timeBeforeActivation, $timeAfterActivation, $end);
    }

    protected function assertScheduleTasksForActivatedAccounts($balanceId, $merchantId, Carbon $lastRunAtFrom, Carbon $lastRunAtEnd, Carbon $nextRunAt)
    {
        $scheduleTask = $this->getDbEntity('schedule_task', [
            'entity_id'     => $balanceId,
            'entity_type'   => 'balance',
        ])->toArray();

        $this->assertEquals($merchantId, $scheduleTask['merchant_id']);

        $this->assertGreaterThanOrEqual($lastRunAtFrom->timestamp, $scheduleTask[Schedule\Task\Entity::LAST_RUN_AT]);
        $this->assertLessThanOrEqual($lastRunAtEnd->timestamp, $scheduleTask[Schedule\Task\Entity::LAST_RUN_AT]);
        $this->assertEquals($nextRunAt->timestamp, $scheduleTask[Schedule\Task\Entity::NEXT_RUN_AT]);
    }

    protected function createScheduleTaskForMerchantAndBalance($merchantId, $balanceId)
    {
        $schedule = $this->getDbEntity('schedule', [
            'type' => Schedule\Type::FEE_RECOVERY,
        ]);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $balance = $this->getDbEntityById('balance', $balanceId);

        $scheduleTaskInput = [
            'type'          => 'fee_recovery',
            'schedule_id'   => $schedule['id'],
        ];

        $scheduleTask = (new Schedule\Task\Core)->create($merchant, $balance , $scheduleTaskInput);

        $scheduleTask->saveOrFail();

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        $now = Carbon::now(Timezone::IST);

        [$start, $end] = (new FeeRecovery\Core())->getNextInterval($now);

        $this->fixtures->edit('schedule_task', $scheduleTask['id'], [
            'next_run_at'  => $end->timestamp,
        ]);

    }
}
