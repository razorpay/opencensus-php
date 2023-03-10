<?php

namespace RZP\Mail\BankingAccount\DocketMail;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;

/**
 * Folowing should be added in view data
 * * subject
 * * merchantName
 * * refNo
 * * entityType
 * * address
 * * city
 * * pocName
 * * pocPhoneNumber
 * * attachment_url
 */
class DocketMail extends Base
{
    const SUBJECT = "RazorpayX | Stamp Paper and Docs | %s | %s | %s";

    public $timeout = 300;

    /** @var array $recipient */
    protected $recipient;

    /** @var array $otherRecipients */
    protected $otherRecipients;

    /**
     * @param array $viewData
     */
    public function __construct(array $viewData, array $recipient, array $otherRecipients)
    {
        parent::__construct($viewData);

        $this->recipient = $recipient;

        $this->otherRecipients = $otherRecipients;
    }

    protected function addSubject()
    {
        $data = $this->viewData;

        $this->subject($data['subject']);

        return $this;
    }

    protected function addRecipients()
    {
        $recipient = $this->recipient;

        $this->to($recipient['email'], $recipient['name']);

        return $this;
    }

    protected function addMailData()
    {
        $data = $this->viewData;

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
       $this->view('emails.banking_account.docket');
    
       return $this;
    }

    protected function addAttachments()
    {
        try {

            $url = $this->viewData['attachment_url'];

            $content = file_get_contents($url);

            if ($content)
            {
                app('trace')->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                    'stage'     => 'email >> download pdf',
                    'message'   => 'File get content successful'
                ]);
            }
            else
            {
                app('trace')->error(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                    'stage' => 'email >> download pdf',
                    'error' => 'File get content failed',
                    'url'   => $url
                ]);

                return $this;
            }

            $timestamp = Carbon::createFromTimestamp(time(), Timezone::IST)->format('Y-m-d--H-i');

            $fileName = 'Docket-'. $timestamp .'.pdf';

            if (file_put_contents($fileName, $content))
            {
                app('trace')->info(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                    'stage'     => 'email >> download pdf',
                    'message'   => 'File put content successful'
                ]);
            }
            else
            {
                app('trace')->error(TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_INFO, [
                    'stage' => 'email >> download pdf',
                    'error' => 'File put content failed'
                ]);

                return $this;
            }

            $this->attach($fileName, [
                'as' => $fileName,
                'mime-type' => 'application/pdf',
            ]);
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_DOCKET_INITIATION_ERROR, [
                    'stage' => 'email >> attachments',
                    'attachments' => $this->viewData['attachments']
                ]);
        }

        return $this;
    }

    protected function addCc()
    {
        // For testing only
        $this->cc('umakant.vashishtha@razorpay.com', 'Umakant Vashishtha');

        foreach ($this->otherRecipients as $otherRecipient)
        {
            $this->cc($otherRecipient['email'], $otherRecipient['name']);
        }

        return $this;
    }

}
