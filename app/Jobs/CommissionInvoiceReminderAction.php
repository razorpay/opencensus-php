<?php

namespace RZP\Jobs;

use App;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Partner;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Core as MerchantCore;

class CommissionInvoiceReminderAction extends Job
{
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    const MAX_RETRY_ATTEMPT = 1;

    protected $partnerIds;

    protected $retry;

    public $timeout = 150;

    public function __construct(string $mode, array $partnerIds, int $retry = null)
    {
        parent::__construct($mode);

        $this->partnerIds = $partnerIds;
        $this->retry      = $retry ?? 0;
    }

    /**
     * Fetch current F.Y. invoices for each partner and Send commission invoice reminder to partners via mail and sms.
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_REMINDER_REQUEST,
            [
                'mode'             => $this->mode,
                'partner_ids'      => $this->partnerIds,
                'retry_count'      => $this->retry,
            ]
        );

        $timeStarted = microtime(true);
        $failedIds = [];

        $core       = (new Partner\Commission\Invoice\Core);
        $startTime  = $core->getStartTimeForCommissionInvoiceReminders();
        $app        = App::getFacadeRoot();
        $experiment = $app['config']->get('app.send_commission_invoice_reminders_exp_id');

        foreach ($this->partnerIds as $partnerId)
        {
            try
            {
                $properties = [
                    'id'            => $partnerId,
                    'experiment_id' => $experiment,
                ];

                $isExpEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable', TraceCode::SEND_COMMISSION_INVOICE_REMINDERS_SPLITZ_ERROR);

                if($isExpEnabled)
                {
                    $invoices   = $this->repoManager->commission_invoice->fetchIssuedInvoicesByMerchantId($partnerId, $startTime);

                    $invoiceCount = $invoices->count();

                    $partner          = $this->repoManager->merchant->findOrFail($partnerId);
                    $activationStatus = $core->getApplicablePartnerActivationStatus($partner);

                    // Don't send reminders to the partner in rejected state. [PLAT-483]
                    if( $activationStatus === DetailStatus::REJECTED)
                    {
                        continue;
                    }
                    $core->sendCommissionReminderMail($invoices, $partner, $activationStatus);
                    $core->sendCommissionReminderSms($invoiceCount, $partner, $activationStatus);

                    $this->trace->info(
                        TraceCode::COMMISSION_INVOICE_REMINDER_SUCCESS,
                        [
                            'mode'             => $this->mode,
                            'id'               => $partnerId,
                            'invoice_count'    => $invoiceCount,
                        ]
                    );
                }
            }
            catch (\Throwable $e)
            {
                $failedIds[] = $partnerId;

                $this->countJobException($e);
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::COMMISSION_INVOICE_REMINDER_ERROR,
                    [
                        'mode'          => $this->mode,
                        'partner_id'    => $partnerId
                    ]
                );
            }
        }

        $timeTaken = microtime(true) - $timeStarted;
        $timeTakenMilliSeconds = (int) $timeTaken * 1000;

        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_REMINDER_JOB_MESSAGE_COMPLETED,
            [
                'mode'              => $this->mode,
                'time_taken_ms'     => $timeTakenMilliSeconds,
                'failed_count'      => count($failedIds),
            ]
        );

        if(count($failedIds) > 0 && $this->retry < self::MAX_RETRY_ATTEMPT)
        {
            CommissionInvoiceReminderAction::dispatch($this->mode, $failedIds, $this->retry + 1);
        }

        $this->delete();
    }
}
