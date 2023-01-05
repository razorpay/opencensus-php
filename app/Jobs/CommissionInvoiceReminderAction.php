<?php

namespace RZP\Jobs;

use App;
use RZP\Models\Partner;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Core as MerchantCore;


class CommissionInvoiceReminderAction extends Job
{
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 1;

    protected $partnerIds;

    protected $retry;

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

        $failedIds = [];

        $core       = (new Partner\Commission\Invoice\Core);
        $startTime  = $core->getStartTimeForCommissionInvoiceReminders();

        foreach ($this->partnerIds as $partnerId)
        {
            try
            {
                $app = App::getFacadeRoot();

                $properties = [
                    'id'            => $partnerId,
                    'experiment_id' => $app['config']->get('app.send_commission_invoice_reminders_exp_id'),
                ];

                $isExpEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable', TraceCode::SEND_COMMISSION_INVOICE_REMINDERS_SPLITZ_ERROR);

                if($isExpEnabled)
                {
                    $invoices   = $this->repoManager->commission_invoice->fetchIssuedInvoicesByMerchantId($partnerId, $startTime);

                    $invoiceCount = $invoices->count();

                    $core->sendCommissionReminderMail($invoices, $partnerId);
                    $core->sendCommissionReminderSms($invoiceCount, $partnerId);

                    $this->trace->info(
                        TraceCode::COMMISSION_INVOICE_REMINDER_REQUEST,
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
                        'mode' => $this->mode,
                    ]
                );
            }
        }

        if(count($failedIds) > 0 && $this->retry < self::MAX_RETRY_ATTEMPT)
        {
            CommissionInvoiceReminderAction::dispatch($this->mode, $failedIds, $this->retry + 1);
        }

        $this->delete();
    }
}
