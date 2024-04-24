<?php


namespace RZP\Mail\CardlessEmiNceAdjustment;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class FailAxioAdjustmentSettlement extends Mailable
{
    const SUBJECT              = 'PB/Axio subvention adjustment Fail %s ';

    protected $amount;

    protected $merchantId;

    public function __construct(int $amount, string $merchantId)
    {
        parent::__construct();
        $this->amount = $amount;
        $this->merchantId = $merchantId;
    }

    protected function addMailData()
    {
        $data['body'] = 'Adjustment Failed for '. $this->merchantId;

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
        //To-DO Verify the Fail adjsutmetn recepeints
        $this->from(Constants::MAIL_ADDRESSES[Constants::FINOPS],
            Constants::HEADERS[Constants::FINOPS]);

        return $this;
    }

    protected function addRecipients()
    {
        //To-DO Verify the Fail adjsutmetn recepeints
        $this->to(Constants::MAIL_ADDRESSES[Constants::FINOPS],
            Constants::HEADERS[Constants::FINOPS]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

}
