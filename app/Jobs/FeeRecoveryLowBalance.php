<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\FeeRecovery;
use RZP\Models\BankingAccount;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Mail;
use RZP\Models\BankingAccountService;
use RZP\Mail\FeeRecovery\LowBalanceAlert;
use RZP\Models\Settlement\SlackNotification;

class FeeRecoveryLowBalance extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const DELAY = 30;

    protected $trace;

    protected $metricsEnabled = true;

    protected string $merchantId;

    protected array $balanceIds;

    protected ?string $businessId;

    protected ?string $bankingAccountId;

    protected ?string $accountNumber;

    protected string $action;

    protected bool $isPendingFeeHigh;

    public function __construct(string $mode,
                                array $input)
    {
        parent::__construct($mode);

        $this->merchantId = $input[FeeRecovery\Entity::MERCHANT_ID];

        $this->balanceIds = $input[FeeRecovery\Constants::BALANCE_IDS];

        $this->businessId = $input[FeeRecovery\Constants::BUSINESS_ID];

        $this->bankingAccountId = $input[FeeRecovery\Constants::BANKING_ACCOUNT_ID];

        $this->accountNumber = $input[FeeRecovery\Constants::ACCOUNT_NUMBER];

        $this->action = $input[FeeRecovery\Constants::ACTION];

        $this->isPendingFeeHigh = false;

        $this->validateAction();
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::FEE_RECOVERY_LOW_BALANCE_CRON_PROCESS,
            [
                'merchant_id'           => $this->merchantId,
                'balance_ids'           => $this->balanceIds,
                'business_id'           => $this->businessId,
                'banking_account_id'    => $this->bankingAccountId,
                'action'                => $this->action
            ]);

        try
        {
            $this->computeIsPendingFeeHigh();

            switch ($this->action)
            {
                case FeeRecovery\Constants::LOW_BALANCE_ALERT:
                    $this->processLowBalanceAlert();
                    break;
                case FeeRecovery\Constants::AUTOMATED_BLOCKING:
                    $this->processAutomatedBlocking();
                    break;
                case FeeRecovery\Constants::AUTOMATED_UNBLOCKING:
                    $this->processAutomatedUnblocking();
                    break;
            }

            $this->trace->info(
                TraceCode::FEE_RECOVERY_LOW_BALANCE_CRON_COMPLETED,
                [
                    'merchant_id'           => $this->merchantId,
                    'action'                => $this->action
                ]);
        }
        catch(\Throwable $ex)
        {
            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS ||
                ($ex instanceof Exception\LogicException))
            {
                $this->delete();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_LOW_BALANCE_FAILURE_DELETE_JOB,
                    [
                        'merchant_id'           => $this->merchantId,
                        'action'                => $this->action
                    ]
                );
            }
            else
            {
                $this->release(self::DELAY);

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_LOW_BALANCE_CRON_FAILED,
                    [
                        'merchant_id'           => $this->merchantId,
                        'action'                => $this->action
                    ]
                );
            }
        }
    }

    private function processLowBalanceAlert()
    {
        if (count($this->balanceIds) !== 1)
        {
            throw new Exception\LogicException("Only 1 balance_id applicable for FeeRecovery Low Balance Alert");
        }

        $balanceId = $this->balanceIds[0];

        if ($this->isPendingFeeHigh)
        {
            // Send email to merchant
            /** @var Merchant\Entity $merchant */
            $merchant = (new Merchant\Repository())->findOrFail($this->merchantId);

            $mailable = new LowBalanceAlert([
                Merchant\Entity::MERCHANT_ID            => $this->merchantId,
                Merchant\Entity::EMAIL                  => $merchant->getEmail(),
                Merchant\Entity::NAME                   => $merchant->getName(),
                FeeRecovery\Constants::ACCOUNT_NUMBER   => $this->accountNumber,
            ]);

            Mail::queue($mailable);

            $this->trace->info(TraceCode::FEE_RECOVERY_LOW_BALANCE_ALERT_EMAIL_QUEUED, [
                FeeRecovery\Entity::MERCHANT_ID   => $this->merchantId,
                FeeRecovery\Entity::BALANCE_ID    => $balanceId
            ]);

            // Update email sent timestamp on BAS
            $currentTime = Carbon::now(Timezone::IST);

            (new BankingAccountService\Service())->updateFeeRecoveryMetadata(
                $this->businessId,
                $this->bankingAccountId,
                [
                    FeeRecovery\Constants::FEE_RECOVERY_EMAIL_SENT_AT  => $currentTime->timestamp,
                ]
            );
        }
    }

    private function processAutomatedBlocking()
    {
        if (!$this->isPendingFeeHigh)
        {
            return;
        }

        $this->addAndRemoveFeature(Feature\Constants::PAYOUT_LOW_BALANCE, Feature\Constants::PAYOUT);

        /** @var Merchant\Entity $merchant */
        $merchant = (new Merchant\Repository())->findOrFail($this->merchantId);

        (new SlackNotification)->send('Payouts disabled: Low Balance', [
            'text'  => sprintf('Payouts disabled for %s [%s, %s]// <@UU34X4PCG>', $this->merchantId, $merchant->getName(), $merchant->getEmail()),
        ], null, 0, FeeRecovery\Constants::FEE_RECOVERY_SLACK_CHANNEL);
    }

    private function processAutomatedUnblocking()
    {
        if ($this->isPendingFeeHigh)
        {
            return;
        }

        $this->addAndRemoveFeature(Feature\Constants::PAYOUT, Feature\Constants::PAYOUT_LOW_BALANCE);
    }

    private function computeIsPendingFeeHigh()
    {
        foreach ($this->balanceIds as $balanceId)
        {
            // checking for queued recovery payouts amount first as checking actual recovery payouts amount is an expensive DB query
            $queuedRecoveryPayoutsAmount = (new Payout\Repository)->fetchSumQueuedFeeRecoveryPayoutsForMerchant($this->merchantId, $balanceId);

            if ($queuedRecoveryPayoutsAmount->getAmount() > FeeRecovery\Constants::MIN_BALANCE_AMOUNT)
            {
                $this->isPendingFeeHigh = true;

                return;
            }

            $actualRecoveryPayoutsAmount = (new BankingAccount\Entity)->fetchOutstandingAmountToBeRecovered($this->merchantId, $balanceId, \RZP\Base\ConnectionType::PAYMENT_FETCH_REPLICA);

            if ($actualRecoveryPayoutsAmount > FeeRecovery\Constants::MIN_BALANCE_AMOUNT)
            {
                $this->isPendingFeeHigh = true;

                return;
            }
        }
    }

    private function addAndRemoveFeature(string $featureToAdd, string $featureToRemove)
    {
        (new Feature\Repository)->transaction(function() use ($featureToAdd, $featureToRemove)
        {
            // delete feature for merchant
            $feature = (new Feature\Repository)->findByEntityTypeEntityIdAndName(
                Merchant\Entity::MERCHANT,
                $this->merchantId,
                $featureToRemove
            );

            if (!empty($feature))
            {
                (new Feature\Core)->delete($feature);
            }

            // add feature for merchant
            $feature = (new Feature\Repository)->findByEntityTypeEntityIdAndName(
                Merchant\Entity::MERCHANT,
                $this->merchantId,
                $featureToAdd
            );

            if (empty($feature))
            {
                (new Feature\Core)->create([
                    Feature\Entity::ENTITY_ID   => $this->merchantId,
                    Feature\Entity::ENTITY_TYPE => Merchant\Entity::MERCHANT,
                    Feature\Entity::NAME        => $featureToAdd,
                ]);
            }
        });
    }

    private function validateAction()
    {
        if (in_array($this->action, [
            FeeRecovery\Constants::LOW_BALANCE_ALERT,
            FeeRecovery\Constants::AUTOMATED_UNBLOCKING,
            FeeRecovery\Constants::AUTOMATED_BLOCKING,
        ], true))
        {
            return;
        }

        throw new Exception\LogicException(sprintf('Invalid action (%s) sent for FeeRecoveryLowBalance Job', $this->action));
    }
}
