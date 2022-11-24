<?php

namespace RZP\Mail\PayoutDowntime;

use App;
use Symfony\Component\Mime\Email;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;

use RZP\Trace\TraceCode;
use RZP\Mail\Base\Common;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Validator;
use RZP\Models\PayoutDowntime\Constants;
use RZP\Models\PayoutDowntime\Repository as Repository;

class PayoutDowntimeMail extends Mailable
{

    protected $data;

    private   $downtimeId;

    public function __construct(array $data, string $downtimeId)
    {
        parent::__construct();

        $this->data = $data;

        $this->downtimeId = $downtimeId;
    }

    protected function addRecipients()
    {
        $from = $this->data[Constants::FROM];

        $this->from($from);

        $cc = $this->data[Constants::CC];

        $this->cc($cc);

        $bcc = $this->data[Constants::BCC];

        $this->bcc($bcc);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->data[Constants::SUBJECT] ?? 'Important Update for your RazorpayX account.';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $emailParams = [
            'email_message' => $this->data[Constants::EMAIL_MESSAGE],
        ];

        $this->with($emailParams);

        return $this;
    }

    protected function addHtmlView()
    {

        $view = ($this->data[Constants::EMAIL_TYPE] === Constants::ENABLED) ? 'emails.payout_downtime.enabled' : 'emails.payout_downtime.disabled';

        $this->view($view);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYOUT_DOWNTIME_NOTIFICATION);
        });

        return $this;
    }

    public function send($mailer)
    {
        $app = App::getFacadeRoot();

        /** @var Trace $trace */
        $trace = $app['trace'];

        $repo = new Repository();

        try
        {
            parent::send($mailer);

            $trace->info(TraceCode::PAYOUT_DOWNTIME_EMAIL_STATUS, ['mail' => 'sent']);

            $repo->updateEmailStatus($this->downtimeId, Constants::SENT);
        }
        catch (\Throwable $e)
        {
            $repo->updateEmailStatus($this->downtimeId, Constants::FAILED);

            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_DOWNTIME_EMAIL_STATUS_UPDATE_ERROR
            );

            if (($e instanceof GuzzleClientException) !== true)
            {
                throw $e;
            }
        }
    }

    protected function isValidRecipient(): bool
    {
        if (filled($this->cc) === true)
        {
            $emailValidator = new Validator;
            $recipientEmail = $this->cc[0]['address'];

            return (($recipientEmail !== 'void@razorpay.com') and
                    ($emailValidator->isSendable($recipientEmail) === true));
        }

        return false;
    }
}
