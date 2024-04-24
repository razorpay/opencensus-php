<?php


namespace RZP\Mail\CardlessEmiNceAdjustment;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class SuccessPolicyBazaarAdjustmentSettlement extends Mailable
{
    const SUBJECT              = 'PB/Axio subvention adjustment %s ';

    protected $amount;

    protected $adjustmentID;

    public function __construct(int $amount, string $adjustmentID)
    {
        parent::__construct();
        $this->amount = $amount;
        $this->adjustmentID = $adjustmentID;
    }

    protected function addMailData()
    {
        $data['body'] = 'Positive Adjustment Done Successfully of Rs. ' . $this->amount .
            "\n".
            ' Adjustment ID ' . $this->adjustmentID . '.';

        $this->with($data);

        return $this;
    }

    protected function addSubject()
    {
        $date = Carbon::now(Timezone::IST)->format('Y-m-d D');
        $subject = self::SUBJECT . $date;

        $this->subject($subject);
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::PG_SETTLEMENT],
            Constants::HEADERS[Constants::PG_SETTLEMENT]);

        return $this;
    }


    protected function addReplyTo()
    {
        $this->from('apoorva.m@razorpay.com');

        return $this;
    }

    protected function addRecipients()
    {
        $this->to('apoorva.m@razorpay.com');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }
}
