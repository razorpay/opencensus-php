<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;

class EntityProcessor extends Base\Core
{
    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    protected $fta;

    protected $source;

    protected $sendFailureEmailToMerchant = false;

    /**
     * @var bool
     * Denotes if the webhook should be fired.
     */
    protected $fireWebhook = false;

    protected $holdFunds = false;

    protected $dashboardUrl;

    public function __construct(Attempt\Entity $fta)
    {
        parent::__construct();

        $this->fta = $fta;

        $this->source = $fta->source;

        $this->reconciledAt = Carbon::now(Timezone::IST)->timestamp;

        $this->dashboardUrl = $this->app['config']->get('applications.dashboard.url');
    }

    /**
     * Returns an array with the following 2 keys
     * - entity
     * - fire_webhook
     *
     */
    public function process(): array
    {
        $this->updateEntities();

        if ($this->sendFailureEmailToMerchant === true)
        {
            $this->sendReconciliationFailureEmail();
        }

        return [
            'entity'        => $this->source,
            'fire_webhook'  => $this->fireWebhook
        ];
    }

    protected function updateEntities()
    {
        $this->updateAttemptEntity();

        $this->updateSourceEntity();

        $this->updateMerchantEntity();

        $this->updateTransactionEntity();
    }

    protected function updateTransactionEntity()
    {
        $this->source->transaction->setReconciledAt($this->reconciledAt);

        $this->source->transaction->saveOrFail();
    }

    protected function updateMerchantEntity()
    {
        if ($this->holdFunds === true)
        {
            $this->fta->merchant->setHoldFunds(true);

            $this->repo->saveOrFail($this->fta->merchant);
        }
    }

    protected function sendReconciliationFailureEmail()
    {
        if ($this->isMailEnabled() === false)
        {
            return;
        }

        $merchantId = $this->source->getMerchantId();

        $data['merchant_id'] = $merchantId;

        $data['remarks'] = $this->source->getRemarks();

        $data['profile_link'] = $this->dashboardUrl . '#/app/profile';

        // bankAccount for Settlelemt entity, and destination for Payout entity
        $ba = $this->source->destination ?? $this->source->bankAccount;

        $data['last4'] = $ba->getRedactedAccountNumber();

        $data['merchant_email'] = $this->source->merchant->getEmail();

        $data['subject'] = 'Razorpay | Notification for failed settlement on your account ' . $merchantId;

        $settlementFailureMail = new SettlementFailureMail($data);

        Mail::queue($settlementFailureMail);
    }

    protected function isMailEnabled(): bool
    {
        if ($this->source->merchant->isLinkedAccount() === true)
        {
            return false;
        }

        if ($this->app->environment('dev', 'testing') === true)
        {
            return true;
        }

        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        if (Attempt\Type::isNotifyType($this->source->getEntity()) === false)
        {
            return false;
        }

        return true;
    }
}