<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Schedule\Library as ScheduleLibrary;


class SettlementTransfer extends Base
{
    public function fillDetails()
    {
        $this->txn->setAmount($this->source->getAmount());

        $this->txn->setChannel($this->source->merchant->getChannel());
    }

    public function setFeeDefaults()
    {
        $this->fees = 0;
        $this->tax  = 0;
    }

    public function calculateFees()
    {
        $this->credit = $this->source->getAmount();
    }

    public function updateTransaction()
    {
        $merchantId = $this->source->getMerchantId();

        $settledAt = $this->getSettledAtTimestampForSettlementTransfer($merchantId);

        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setApiFee(0);

        $this->repo->saveOrFail($this->txn);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->source->balance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    public function getSettledAtTimestampForSettlementTransfer($merchantId, $international = false)
    {
        $merchant =  $this->repo->merchant->findOrFailPublic($merchantId);

        $returnTime = null;

        $scheduleTask = (new ScheduleTask\Core)->getSettlementTransferScheduleForMerchant($merchant);

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        // use schedule from pivot schedule_task if defined and use next run from there
        if ($scheduleTask !== null)
        {
            $schedule = $scheduleTask->schedule;

            $nextRunAt = $scheduleTask->getNextRunAt();

            $returnTime = ScheduleLibrary::getNextApplicableTime(
                                                                $currentTimestamp,
                                                                $schedule,
                                                                $nextRunAt);
        }
        else
        {
            //if the merchant do not have the schedule assigned for the Settlement Transfer transactions
            //then by default we are setting these at T+0 , 4pm for that Settlement Transfer to settle
            $settled_at = Carbon::createFromTimestamp($currentTimestamp, Timezone::IST);

            if($settled_at->hour >= Constants::FOUR_PM)
            {
                $settled_at = Holidays::getNextWorkingDay($settled_at);
            }

            $returnTime = $settled_at->startOfDay()
                                     ->addHours(Constants::FOUR_PM)
                                     ->getTimestamp();
        }

        return $returnTime;
    }
}
