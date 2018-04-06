<?php

namespace RZP\Mail\Reconciliation;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class DailyReconStatusSummary extends Mailable
{
    protected $data;

    protected $gateways;

    protected $params;

    const RECIPIENT_EMAILS_MAP = ['pgrecon@razorpay.com', 'kajol.nigam@razorpay.com'];

    public function __construct(array $gateways,array $params, array $data)
    {
        parent::__construct();

        $this->gateways = $gateways;

        $this->params = $params;

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $to = self::RECIPIENT_EMAILS_MAP;

        $this->to($to);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::RECON];

        $fromHeader = Constants::HEADERS[Constants::RECON];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $date = Carbon::today(Timezone::IST)->format('d-m-y');

        $subject = 'Razorpay | Daily Reconciliation Summary for ' . $date;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $message = '';

        foreach ($this->data as $entity => $data) {

            foreach ($data as $date => $gatewayData) {

                $message .= '<b>' . $entity . ' Reconciliation summary for '.$date.' - </b><br /><br />';

                $message .= $this->getTabularFormattedReconSummary($gatewayData);

            }
        }

        $mailData['body'] = $message;

        $this->with($mailData);

        return $this;
    }

    protected function getTabularFormattedReconSummary(array $reconData)
    {
        $message = '<table border="1">';

        $message .= '<th>Gateway</th>';

        //
        // Set the headers of the tables as params
        //
        foreach ($this->params as $param)
        {
            $message .= '<th>' . $param . '</th>';
        }


        foreach ($reconData as $date => $gatewayData)
        {
            $message .= '<tr><td>' . $gatewayData['gateway'] . '</td>';

            foreach ($this->params as $param)
            {

                $message .= '<td>' . ($gatewayData[$param] ?? 0) . '</td>';
            }

            $message .= '</tr>';
        }

        $message .= '</table><br />';

        return $message;
    }

    protected function getTableFormattedEntites(array $reconData)
    {
        $message = '<table border="1">';

        $message .= '<th>Gateway</th><th>Entity ID</th><th>Created At</th>';

        foreach ($reconData as $gateway => $gatewayData)
        {
            foreach ($gatewayData as $entityId => $createdAt)
            {
                $message .= '<tr> <td>' . $gateway . '</td>';

                $message .=  '<td>' . $entityId . '</td><td>' . $createdAt . '</td>';

                $message .= '</tr>';
            }
        }

        $message .= '</table><br />';

        return $message;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_RECON_SUMMARY);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }
}