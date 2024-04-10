<?php

namespace RZP\Jobs\Ledger;

use App;
use Exception;
use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Merchant\Constants as MerchantConstants;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Account as MerchantAccount;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Credits\Entity as CreditsEntity;

class AmountCreditsExpiryReverseShadow extends Job
{

    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 1;

    protected $app;
    protected $repo;

    protected $creditId;

    public function __construct(string $mode, string $creditId)
    {
        parent::__construct($mode);

        $this->creditId = $creditId;
    }

    public function handle()
    {
        parent::handle();

        $this->app =  App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        if($this->mode === Mode::TEST)
        {
            return;
        }

        try
        {
            $credit = $this->repo->credits->findOrFail($this->creditId);

            $this->registerAmountCreditExpiryReminder($credit);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::FAILED_TO_REGISTER_AMOUNT_CREDIT_EXPIRY,
                [
                    "credit id" => $this->creditId
                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->delete();

            $this->trace->info(TraceCode::QUEUE_ENTRY_DELETED, [
                "credit_id" => $this->creditId,
            ]);
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    protected function registerAmountCreditExpiryReminder(CreditsEntity $credit)
    {
        $namespace = MerchantConstants::AMOUNT_CREDITS_EXPIRY_NAMESPACE;

        $url = sprintf('reminders/send/%s/credit/%s/%s', $this->mode, $namespace, $credit->getId());

        $request = [
            LedgerConstants::NAMESPACE     => $namespace,
            LedgerConstants::ENTITY_ID     => $credit->getId(),
            LedgerConstants::ENTITY_TYPE   => MerchantConstants::CREDIT,
            MerchantConstants::REMINDER_DATA => [
                MerchantConstants::EXPIRED_AT => $credit->getExpiredAt(),
            ],
            MerchantConstants::CALLBACK_URL  => $url,
        ];

        $merchant = $credit->merchant;

        // Reminders not created for merchants that are not onboarded onto reverse shadow
        if($merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            return;
        }

        try
        {
            $response = $this->app['reminders']->createReminder($request, MerchantAccount::SHARED_ACCOUNT );

            $reminderId = array_get($response, 'id');

            $this->trace->count(Metric::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_CREATED, [
                'mode' => $this->mode,
            ]);

            $this->trace->info(TraceCode::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_CREATED, [
                'credit_id'   => $credit->getId(),
                'reminder_id' => $reminderId,
            ]);
        }
        catch (\Throwable $exception) {

            $this->trace->count(Metric::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_FAILURE, [
                'mode' => $this->mode,
            ]);

            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_FAILURE,
                [
                    'entity_id'     => $credit->getId(),
                ]);
        }
    }
}
