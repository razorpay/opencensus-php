<?php

namespace RZP\Models\Payout\DualWrite;

use App;
use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\FeeRecovery;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Purpose;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use Illuminate\Foundation\Application;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\Merchant\Core as MerchantCore;


class Processor
{
    const MUTEX_LOCK_TIMEOUT_PS_DUAL_WRITE = 30;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->mutex = $this->app['api.mutex'];
    }

    public function dualWriteDataForPayoutId(string $payoutId)
    {
        $this->mutex->acquireAndRelease(
            'payout_dual_write_' . $payoutId,
            function() use ($payoutId) {
                $this->repo->transaction(function() use ($payoutId)
                {
                    /** @var Entity $apiPayoutBeforeDualWrite */
                    $apiPayoutBeforeDualWrite= $this->repo->payout->find($payoutId);
                    $previousStatus= null;

                    if($apiPayoutBeforeDualWrite!==null)
                    {
                        $previousStatus= $apiPayoutBeforeDualWrite->getStatus();
                    }


                    (new Payout)->dualWritePSPayout($payoutId);

                    (new Reversal)->dualWritePSReversal($payoutId);

                    (new PayoutSource)->dualWritePSPayoutSources($payoutId);

                    (new PayoutDetails)->dualWritePSPayoutDetails($payoutId);

                    (new WorkflowEntityMap)->dualWritePSWorkflowEntityMap($payoutId);

                    (new PayoutStatusDetails)->dualWritePSPayoutStatusDetails($payoutId);

                    (new IdempotencyKey)->dualWritePSPayoutIdempotencyKey($payoutId);

                    $this->makeFeeRecoveryIfApplicableForPSCAPayout($payoutId, $previousStatus);

                });
            },
            self::MUTEX_LOCK_TIMEOUT_PS_DUAL_WRITE,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    /**
     * @param string $payoutId
     * @param mixed $previousStatus
     * @return void
     * @throws BadRequestException
     * @throws \Throwable
     */
    function makeFeeRecoveryIfApplicableForPSCAPayout(string $payoutId, mixed $previousStatus): void
    {
        try {
            /** @var Entity $apiPayout */
            $apiPayout = $this->repo->payout->find($payoutId);

            $status = $apiPayout->getStatus();

            $this->trace->info(
                TraceCode::PS_CA_FEE_RECOVERY_FLOW_INIT,
                [
                    "payout_previous_status" => $previousStatus,
                    "payout_current_status" => $apiPayout->getStatus()
                ]
            );

            // Ignoring reversed status since this only comes on T+1, and a delay of 24hrs is not expected in dual write
            if(($status === Status::PROCESSED || $status === Status::FAILED) && $previousStatus === null)
            {
                // TODO: move experiment ID to a const
                $properties = [
                    "experiment_id" => "fee_recovery_dual_write_flow",
                    "id" => $apiPayout->getMerchantId(),
                ];

                $expResp = (new MerchantCore())->isSplitzExperimentEnable($properties, "enabled");

                $this->trace->info(
                    TraceCode::FEE_RECOVERY_SPLITZ_RESPONSE,
                    [
                        "properties" => $properties,
                        "experiment_response" => $expResp
                    ]
                );

                $previousStatus = $expResp ? Status::INITIATED : $previousStatus;
            }

            if ($apiPayout->isBalanceAccountTypeDirect() === true and $previousStatus !== null)
            {
                if ($previousStatus === $status)
                {
                    return;
                }

                switch ($status)
                {
                    case Status::PROCESSED:
                        (new FeeRecovery\Core)->handlePayoutStatusUpdate($apiPayout);
                        break;

                    case Status::REVERSED:
                        $reversal = $this->repo->reversal->findReversalForPayout($payoutId);
                        (new FeeRecovery\Core)->handlePayoutStatusUpdate($apiPayout, $previousStatus, $reversal);
                        break;

                    case Status::FAILED:
                        (new FeeRecovery\Core)->handlePayoutStatusUpdate($apiPayout, $previousStatus);
                        break;
                }

            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PS_CA_PAYOUT_STATUS_FAILED,
                [
                    'entity_id' => $payoutId,
                    'entity_type' => EntityConstant::PAYOUT,
                    'status'=> $status
                ]
            );

            throw $e;
        }
    }


    public function feeRecoveryForPSCAPayout(Entity $payout)
    {
        try
        {
            if (($payout->isBalanceAccountTypeDirect() === true) and
                ($payout->getFeeType() !== Transaction\CreditType::REWARD_FEE) and
                ($payout->getPurpose() !== Purpose::RZP_CHARGE_COLLECTIONS))
            {
                $featureEnabled = (new \RZP\Models\Merchant\Credits\Service())->isRzpxFeeCreditEnabledForMerchant($payout->merchant);

                if ($featureEnabled === true and $payout->getFeeType() === null)
                {
                    $feeRecovery = (new FeeRecovery\Core)->createFeeRecoveryEntityForSource($payout);

                    if ($feeRecovery === null)
                    {
                        throw new BadRequestException(
                            ErrorCode::BAD_REQUEST_FEE_RECOVERY_MANUAL_COLLECTION_FOR_PAYOUT_INVALID,
                            null,
                            [
                                'entity_id' => $payout->getId(),
                                'entity_type' => 'payout'
                            ]);
                    }

                    $fees = $payout->getFees();

                    $app = App::getFacadeRoot();

                    $app['repo']->transaction(
                        function () use ($feeRecovery, $fees, $payout)
                        {
                            $feeCreditsConsumed = (new \RZP\Models\Merchant\Credits\Transaction\Core)->subtractAndGetMerchantCreditsConsumed($payout->merchant, Entity::FEE_CREDIT, Entity::BANKING, $fees, $payout);

                            if ($feeCreditsConsumed !== 0 and $feeCreditsConsumed === $fees)
                            {
                                // We will set the payout_id in place of recovery payout_id.
                                $feeRecovery->setRecoveryPayoutId($payout->getId());

                                (new FeeRecovery\Core)->updateFeeRecoveryStatusForFeeCredit($feeRecovery, FeeRecovery\Status::RECOVERED);
                            }
                        });
                }
                else
                {
                    (new FeeRecovery\Core)->createFeeRecoveryEntityForSource($payout, false);
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PS_CA_FEE_RECOVERY_FAILED,
                [
                    'entity_id' => $payout->getId(),
                    'entity_type' => EntityConstant::PAYOUT
                ]
            );

        }
    }

}

