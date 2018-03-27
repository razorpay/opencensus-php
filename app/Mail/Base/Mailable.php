<?php

namespace RZP\Mail\Base;

use App;
use Config;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;

class Mailable extends BaseMailable
{
    use Queueable;

    #TODO : decrease the number of attempts after daily files are fixed
    public $tries = 5;

    public $taskId;

    public $mode;

    protected $emailValidator;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->taskId = $app['request']->getTaskId();
        $this->mode   = $app['basicauth']->getMode();
        $this->queue  = $this->getQueueName();
    }

    public function build()
    {
        return $this->addSender()
                    ->addRecipients()
                    ->addCc()
                    ->addBcc()
                    ->addReplyTo()
                    ->addHtmlView()
                    ->addTextView()
                    ->addSubject()
                    ->addMailData()
                    ->addAttachments()
                    ->addHeaders();
    }

    public function send(MailerContract $mailer)
    {
        $app = App::getFacadeRoot();
        $trace = $app['trace'];

        try
        {
            Container::getInstance()->call([$this, 'build']);

            if ($this->isValidRecipient() === true)
            {
                $mailer->send($this->buildView(), $this->buildViewData(), function ($message) {
                    $this->buildFrom($message)
                         ->buildRecipients($message)
                         ->buildSubject($message)
                         ->buildAttachments($message)
                         ->runCallbacks($message);
                });
            }
        }
        catch (\Throwable $e)
        {
            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::MAILER_JOB_ERROR,
                                   [
                                        'from'    => $this->from,
                                        'to'      => $this->to,
                                        'subject' => $this->subject,
                                        'mailable' => get_class($this)
                                   ]);

            // After logging the exception caught, we rethrow it so that the
            // retry mechanism for mails is triggerred unless the exception
            // was a guzzle client exception (i.e 4XX errors), in which case,
            // retrying the request would just cause the request to fail.
            if (($e instanceof GuzzleClientException) !== true)
            {
                throw $e;
            }
        }
    }

    /**
     * Overridden: We use sub classed SendQueuedMailable which sets request id
     * and task id for tracing.
     *
     * Queue the message for sending.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function queue(Queue $queue)
    {
        $connection = property_exists($this, 'connection') ? $this->connection : null;
        $queueName  = property_exists($this, 'queue') ? $this->queue : null;

        return $queue
                ->connection($connection)
                ->pushOn($queueName ?: null, new SendQueuedMailable($this));
    }

    /**
     * Stub method to add mail sender. To be implemented by child classes
     * Use the mailable from() method to add senders
     */
    protected function addSender()
    {
        return $this;
    }

    /**
     * Stub method to add mail recipients. To be implemented by child classes
     * Use the mailable to() method to add recipients
     */
    protected function addRecipients()
    {
        return $this;
    }

    /**
     * Stub method to add reply to addresses. To be implemented by child classes.
     * Use the mailable replyTo() method to add this
     */
    protected function addReplyTo()
    {
        return $this;
    }

    /**
     * Stub method to add cc addresses. To be implemented by child classes.
     * Use the mailable cc() method to add mail addresses in cc
     */
    protected function addCc()
    {
        return $this;
    }

    /**
     * Stub method to add bcc addresses. To be implemented by child classes.
     * Use the mailable bcc() method to add mail addresses in bcc
     */
    protected function addBcc()
    {
        return $this;
    }

    /**
     * Stub method to add HTML mail view. To be implemented by child classes
     * Use the mailable view() method to add html view to mail.
     */
    protected function addHtmlView()
    {
        return $this;
    }

    /**
     * Stub method to add text view for mail. To be implemented by child classes
     * Use the mailable text() method to add text view to mail
     */
    protected function addTextView()
    {
        return $this;
    }

    /**
     * Stub method to add subject to mail. To be implemented by child classes.
     * Use the mailable subject() method to add subject to mail
     */
    protected function addSubject()
    {
        return $this;
    }

    /**
     * Stub method to add mail data for use by the template. To be implemented by child classes.
     * Use the mailable with() method to attach any data to the mail body
     */
    protected function addMailData()
    {
        return $this;
    }

    /**
     * Stub method to handle attachments. To be implemented by child classes
     * Use the mailable attach method to attach any file or raw content to mail.
     */
    protected function addAttachments()
    {
        return $this;
    }

    /**
     * Stub method to add mail headers. To be implemented by child clases
     * Use the withSwiftMessage method to get the underlying swift message and
     * add any mail headers like Mailgun header
     */
    protected function addHeaders()
    {
        return $this;
    }

    /**
     * Checks if the recipient email is not void@razorpay.com and is a valid email
     * by checking MX records. Check details here https://github.com/nojacko/email-validator
     *
     * @return boolean
     */
    protected function isValidRecipient(): bool
    {
        if (filled($this->to) === true)
        {
            $emailValidator = new Validator;
            $recipientEmail = $this->to[0]['address'];

            return (($recipientEmail !== 'void@razorpay.com') and
                ($emailValidator->isSendable($recipientEmail) === true));
        }

        return false;
    }

    /**
     * Returns on which queue this mailable should be pushed to.
     * Refer to config/mail.php for the data structure.
     *
     * @return string
     */
    protected function getQueueName(): string
    {
        $key     = snake_case(class_basename($this));
        $default = Config::get('mail.queues.default');

        return Config::get("mail.queues.{$key}", $default);
    }
}
