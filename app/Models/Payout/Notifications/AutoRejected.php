<?php

namespace RZP\Models\Payout\Notifications;

use App;
use Mail;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Entity;
use RZP\Mail\Payout\AutoRejectedPayout as AutoRejectedMail;

class AutoRejected extends Base
{
    protected $payout;

    protected $trace;

    public function __construct(Entity $payout)
    {
        $this->payout = $payout;

        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    // We currently don't support sending SMS for autorejected payouts
    protected function sendSms()
    {
        return;
    }

    protected function sendEmail()
    {
        if (empty($this->payout->merchant->getTransactionReportEmail()) === true)
        {
            return;
        }

        $this->trace->info(TraceCode::PAYOUT_AUTO_REJECTED_EMAIL,
                           [
                               'payout_id' => $this->payout->getId()
                           ]);

        $autoRejectedMail = new AutoRejectedMail($this->payout->getId());

        Mail::queue($autoRejectedMail);
    }
}
