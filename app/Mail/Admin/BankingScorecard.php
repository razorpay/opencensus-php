<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class BankingScorecard extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $to = Constants::MAIL_ADDRESSES[Constants::BANKING_SCORECARD];

        $this->to($to);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.banking_scorecard');

        return $this;
    }

    protected function addSender()
    {
        $fromEmail  = Constants::MAIL_ADDRESSES[Constants::BANKING_SCORECARD];

        $fromHeader = Constants::HEADERS[Constants::BANKING_SCORECARD];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $date    = Carbon::yesterday(Timezone::IST)->format('d-m-y');

        $subject = 'Razorpay Banking | Scorecard for ' . $date;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'yesterday_tpv'              => optional($this->data['yesterdayPayoutAmountFeeAndTaxCount'])->getAttribute('payout_amount_cr'),
            'yesterday_tax_count'        => optional($this->data['yesterdayPayoutAmountFeeAndTaxCount'])->getAttribute('payout_count'),
            'yesterday_fees_collected'   => optional($this->data['yesterdayPayoutAmountFeeAndTaxCount'])->getAttribute('payout_fee_collected'),
            'month_tpv'                  => optional($this->data['payoutAmountFeeAndTaxCountForMonth'])->getAttribute('payout_amount_cr'),
            'month_tax_count'            => optional($this->data['payoutAmountFeeAndTaxCountForMonth'])->getAttribute('payout_count'),
            'month_fees_collected'       => optional($this->data['payoutAmountFeeAndTaxCountForMonth'])->getAttribute('payout_fee_collected'),
            'merchant_data'              => $this->data['yesterdayMerchantsPayoutAmountAndTaxCount']
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::BANKING_SCORECARD);
        });

        return $this;
    }
}
