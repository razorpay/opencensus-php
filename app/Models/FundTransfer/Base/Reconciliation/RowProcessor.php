<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;

abstract class RowProcessor extends Base\Core
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected $row;

    protected $version;

    protected $parsedData;

    /**
     * Entity corresponding to the payment_ref_no column in the file
     */
    protected $reconEntity;

    /**
     * Public id of $reconEntity
     */
    protected $reconEntityId;

    /**
     * Entity returned by method updateEntities. Is either settlement/payout entity
     */
    protected $entity;

    protected $reconciledAt;

    /**
     * Denotes whether the reconEntity is being marked at failed for the first time
     */
    protected $firstFailure = false;

    /**
     * @var bool
     * Denotes if the webhook should be fired.
     */
    protected $fireWebhook;

    protected $dashboardUrl;

    protected $holdFunds = false;

    abstract protected function parseRow();

    abstract protected function getReconciliationStatus();

    abstract protected function updateReconEntity();

    abstract protected function updateSourceEntity();

    public function __construct($row)
    {
        parent::__construct();

        $this->row = $row;

        $this->fireWebhook = false;

        $this->dashboardUrl = $this->app['config']->get('applications.dashboard.url');
    }

    /**
     * Returns an array with the following 2 keys
     * - entity
     * - fire_webhook
     *
     * @param $reconciledAt
     *
     * @return array
     */
    public function process($reconciledAt): array
    {
        $this->reconciledAt = $reconciledAt;

        $this->parseRow();

        $this->fetchEntities();

        if ((empty($this->reconEntity) === true) or
            (empty($this->source) === true))
        {
            $this->trace->error(TraceCode::SETTLEMENT_RECONCILIATION_SKIPPED,
                [
                    'row'           => $this->row,
                    'parsed_data'   => $this->parsedData,
                ]);

            return null;
        }

        $this->getReconciliationStatus();

        $this->entity = $this->updateEntities();

        if ($this->firstFailure === true)
        {
            $this->sendReconciliationFailureEmail();
        }

        return [
            'entity'        => $this->entity,
            'fire_webhook'  => $this->fireWebhook
        ];
    }

    protected function sendReconciliationFailureEmail()
    {
        if ($this->isMailEnabled() === false)
        {
            return;
        }

        $merchantId = $this->entity->getMerchantId();

        $data['merchant_id'] = $merchantId;

        $data['remarks'] = $this->entity->getRemarks();

        $data['profile_link'] = $this->dashboardUrl . '#/app/profile';

        // bankAccount for Settlelemt entity, and destination for Payout entity
        $ba = $this->entity->destination ?? $this->entity->bankAccount;

        $data['last4'] = $ba->getRedactedAccountNumber();

        $data['merchant_email'] = $this->entity->merchant->getEmail();

        $data['subject'] = 'Razorpay | Notification for failed settlement on your account ' . $merchantId;

        $settlementFailureMail = new SettlementFailureMail($data);

        Mail::queue($settlementFailureMail);
    }

    protected function isMailEnabled(): bool
    {
        if ($this->entity->merchant->isLinkedAccount() === true)
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

        if (Type::isNotifyType($this->entity->getEntity()) === false)
        {
            return false;
        }

        return true;
    }

    protected function getEntityStatusNamespace(string $entityName): string
    {
        return Entity::getEntityNamespace($entityName) . '\\Status';
    }

    protected function updateEntities()
    {
        $this->updateReconEntity();

        $this->updateMerchantEntity();

        $this->updateSourceEntity();

        $this->updateTransactionEntity();

        return $this->source;
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
            $this->reconEntity->merchant->setHoldFunds(true);

            $this->repo->saveOrFail($this->reconEntity->merchant);
        }
    }
}