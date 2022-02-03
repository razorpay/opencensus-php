<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand\Bulk;
use RZP\Models\Settlement\Ondemand\Attempt;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer;

class Service extends Base\Service
{
    public function processXSettlementBulkTransfer()
    {
        [$attempt, $transfer] = $this->repo->transaction(function ()
        {
           return $this->app['api.mutex']->acquireAndRelease(
                'settlement_ondemand_transfer_create',
                function()
                {
                    return $this->core()->createSettlementOndemandTransfer();
                });
        });

        if($transfer !== null)
        {
            CreateSettlementOndemandBulkTransfer::dispatch($this->mode, $attempt->getId(), $transfer);
        }

        return $transfer;
    }

    public function processXSettlementTransfer($settlementOndemand)
    {
       [$transfers, $attempts] = $this->repo->transaction(function () use($settlementOndemand)
       {

           [$transfers, $attempts] = $this->core()->createMultipleSettlementOndemandTransfer($settlementOndemand);

            foreach ($transfers as $transfer)
            {
                (new Bulk\Core)->createSettlementOndemandBulk($settlementOndemand, $transfer->getAmount(), $transfer->getId());
            }

            return [$transfers, $attempts];
       });

        for ($i = 0; $i < sizeof($transfers); $i++)
        {
            CreateSettlementOndemandBulkTransfer::dispatch($this->mode, $attempts[$i]->getId(), $transfers[$i]);
        }
    }

    public function markAsProcessed(string $id)
    {
        $settlementOndemandTransfer = (new Repository)->findById($id);

        $this->core()->markAsProcessed($settlementOndemandTransfer);

        return [];
    }

    public function triggerOndemandTransfer(array $settlementOndemandTransferIds)
    {
        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_TRANSFER_RETRY, [
            'settlement_ondemand_transfer_ids' => $settlementOndemandTransferIds,
        ]);

        $result = new Base\PublicCollection;

        foreach ($settlementOndemandTransferIds as $id)
        {
                $this->app['api.mutex']->acquireAndReleaseStrict(
                'settlement_ondemand_transfer_retry'.$id,
                function() use ($id, $result) {

                    try
                    {
                        $settlementOndemandTransfer = (new Repository)->find($id);

                        if ($settlementOndemandTransfer->getStatus() === Status::REVERSED)
                        {
                            $attempt = (new Attempt\Core)->createAttempt($settlementOndemandTransfer);

                            CreateSettlementOndemandBulkTransfer::dispatch($this->mode,
                                                                           $attempt->getId(),
                                                                           $settlementOndemandTransfer)->delay(10);
                        }

                        $result->push([
                            'settlement_ondemand_transfer_id' => $id,
                            'success'                         => true,
                        ]);

                    }
                    catch (\Throwable $e)
                    {
                        $result->push([
                            'settlement_ondemand_transfer_id' => $id,
                            'success'                         => false,
                            'error'                           => [
                                Error::DESCRIPTION       => $e->getMessage(),
                                Error::PUBLIC_ERROR_CODE => $e->getCode(),
                            ]
                        ]);

                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::SETTLEMENT_ONDEMAND_TRANSFER_RETRY_FAILURE,
                            [
                                'settlement_ondemand_transfer_id' => $id
                            ]);
                    }

                });

        }

        return $result->toArrayWithItems();
    }
}
