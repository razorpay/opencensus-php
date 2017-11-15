<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Support\Facades\App;
use Mail;

use RZP\Constants\Entity;
use RZP\Constants\MailTags;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status;

class RowProcessor extends BaseCore
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
     * Denotes if the webhook should be fired.
     * If the entity was updated earlier, this will be set to false.
     */
    protected $fireWebhook;

    protected $dashboardUrl;

    protected $holdFunds = false;

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
    public function process($reconciledAt)
    {
        $this->reconciledAt = $reconciledAt;

        $this->parseRow();

        $this->fetchEntities();

        $this->getReconciliationStatus();

        $this->entity = $this->updateEntities();

        if ($this->firstFailure === true)
        {
            $this->sendReconciliationFailureEmail();
        }

        return ['entity' => $this->entity, 'fire_webhook' => $this->fireWebhook];
    }

    protected function parseRow()
    {
        $utr = trim($this->row[Headings::UTR_NUMBER]);

        if (empty($utr) === true)
        {
            $utr = null;
        }

        $this->parsedData = [
            'payment_ref_no'    => trim($this->row[Headings::PAYMENT_REF_NO] ?? null),
            'utr'               => $utr,
            'bank_status_code'  => trim($this->row[Headings::STATUS_OF_TRANSACTION] ?? null),
            'remarks'           => trim($this->row[Headings::REMARKS] ?? null),
            'payment_date'      => trim($this->row[Headings::PAYMENT_DATE] ?? null),
            'instrument_date'   => trim($this->row[Headings::INSTRUMENT_DATE] ?? null),
            'date_time'         => trim($this->row[Headings::DATE_TIME] ?? null),
            'cms_ref_no'        => trim($this->row[Headings::CMS_REF_NO] ?? null),
        ];

        $this->reconEntityId = $this->parsedData['payment_ref_no'];
    }

    protected function getReconciliationStatus()
    {
        $recordDate = Carbon::createFromFormat('d-M-y', $this->parsedData['payment_date'], Timezone::IST);

        $now = Carbon::now()->getTimestamp();

        $eightFiftyPm = $recordDate->hour(20)->minute(50)->getTimestamp();

        $failureReason = null;

        $class = $this->getEntityStatusNamespace($this->reconEntity->getEntityName());

        $status = $class::FAILED;

        if ($this->parsedData['bank_status_code'] === Status::PROCESSED)
        {
            $remarks = $this->parsedData['remarks'];

            if ((empty($remarks) === false) and
                (in_array($remarks, self::SUCCESS_STATUS) === false))
            {
                $status = $class::FAILED;

                $failureReason = 'Reconciliation';
            }
            else
            {
                $status = $class::PROCESSED;

                // If current time is before 10 pm, dont mark the settlement as
                // processed and update only the utr
                if (($now < $eightFiftyPm) and ($this->env !== 'testing'))
                {
                    $status = $this->reconEntity->getStatus();
                }
            }
        }

        // Verify status
        $oldStatus = $this->reconEntity->getStatus();

        if ($oldStatus !== $status)
        {
            // if already processed
            if ($this->reconEntity->isPendingReconciliation() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Entity Id: ' . $this->reconEntity->getPublicId());
            }

            //
            // Set the fire webhook flag as true only for the settlements that
            // have to be updated in the current settlement cycle. For the settlements
            // that have been settled in the previous cycles, this flag stays false.
            //
            $this->fireWebhook = true;
        }

        $this->parsedData['failure_reason'] = $failureReason;

        $this->parsedData['status'] = $status;
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
}
