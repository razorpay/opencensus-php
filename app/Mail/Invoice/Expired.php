<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;
use RZP\Trace\TraceCode;

class Expired extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment request of %s %s has expired (via Razorpay)',
        Type::ECOD    => ' Payment request of %s %s has expired (via Razorpay)',
        Type::INVOICE => ' Invoice from %s has expired',
    ];

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool {
        $app = \App::getFacadeRoot();

        $traceData = [
            'merchant_id' => $this->data['merchant']['id'],
            'template_view' => $this->view,
        ];

        $app['trace']->info(TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_VIA_EXPIRED , $traceData);

        return true;
    }
}
