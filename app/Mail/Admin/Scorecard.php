<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class Scorecard extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function addRecipients()
    {
        $to = Common::MAIL_ADDRESSES[Common::SCORECARD];

        $this->to($to);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Common::MAIL_ADDRESSES[Common::SCORECARD];

        $fromHeader = Common::FROM_HEADER[Common::SCORECARD];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $date = Carbon::yesterday('Asia/Kolkata')->format('d-m-y');

        $subject = 'Razorpay | Scorecard for ' . $date;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $message = '
            Yesterday Volume        - ' . $this->data['yesterdayVolume']->getAttribute('amount') / 100 . ' <br />
            Monthly Volume till now - ' . $this->data['monthVolume']->getAttribute('amount') / 100 . ' <br /><br />';


        $message .= '
            Yesterday Transactions count        - ' . $this->data['yesterdayVolume']->getAttribute('count') . ' <br />
            Monthly Transactions count till now - ' . $this->data['monthVolume']->getAttribute('count') . ' <br /><br />';

        $message .= 'Yesterday Top Merchants By Volume - <br />';
        $message .= $this->getTabularFormattedMerchantVolumeScorecard($this->data['yesterdayMerchantVolume']);

        $message .= 'Monthly Top Merchants By Volume - <br />';
        $message .= $this->getTabularFormattedMerchantVolumeScorecard($this->data['monthlyMerchantVolume']);

        $mailData['body'] = $message;

        $this->with($mailData);

        return $this;
    }

    protected function getTabularFormattedMerchantVolumeScorecard($volumeData)
    {
        $message = '<table border="1">';

        $message .= '<tr>' .
                    '<th> Merchant Id </th>'.
                    '<th> Name </th>'.
                    '<th> Website </th>'.
                    '<th> Volume </th>'.
                    '<th> Count </th>'.
                    '</tr>';

        foreach ($volumeData as $merchantData)
        {
            $message .= '<tr>';

            $attributes = $merchantData->getAttributes();

            foreach ($attributes as $key => $value)
            {
                $message .= '<td>' . $value . '</td>';
            }

            $message .= '</tr>';
        }

        $message .= '</table><br />';

        return $message;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::SCORECARD);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }
}
