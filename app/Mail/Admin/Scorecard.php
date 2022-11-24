<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Scorecard extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $to = Constants::MAIL_ADDRESSES[Constants::SCORECARD];

        $this->to($to);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SCORECARD];

        $fromHeader = Constants::HEADERS[Constants::SCORECARD];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $date = Carbon::yesterday(Timezone::IST)->format('d-m-y');

        $subject = 'Razorpay | Scorecard for ' . $date;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $message = '
            Yesterday\'s Volume        - ' . $this->data['yesterdayVolume']->getAttribute('amount') / 100 . ' <br />
            Monthly Volume till now - ' . $this->data['monthVolume']['amount'] / 100 . ' <br /><br />';

        $message .= '
            Yesterday\'s Transactions count        - ' . $this->data['yesterdayVolume']->getAttribute('count') . ' <br />
            Monthly Transactions count till now - ' . $this->data['monthVolume']['count'] . ' <br /><br />';

        $message .= 'Yesterday\'s Top Merchants By Volume - <br />';
        $message .= $this->getTabularFormattedMerchantVolumeScorecard($this->data['yesterdayMerchantVolume']);

        // $message .= 'Monthly Top Merchants By Volume - <br />';
        // $message .= $this->getTabularFormattedMerchantVolumeScorecard($this->data['monthlyMerchantVolume']);

        $mailData['body'] = $message;

        $this->with($mailData);

        return $this;
    }

    protected function getTabularFormattedMerchantVolumeScorecard($volumeData)
    {
        //
        // TODO: Refactor this to move to a blade template
        //
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
                if ($key === 'volume')
                {
                    $value = sprintf("%.2f", $value);

                    $value = money_format_IN($value);
                }

                $message .= '<td>' . $value . '</td>';
            }

            $message .= '</tr>';
        }

        $message .= '</table><br />';

        return $message;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
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
