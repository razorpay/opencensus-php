<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Batch as BatchModel;

class Base extends Mailable
{
    // Following static variables are internal to Mail\Batch model for
    // re-usability as they followed very same construct.

    /**
     * Mail tag to add in mail headers
     *
     * @var string
     */
    protected static $mailTag;

    /**
     * Accessors key for sender's address and header
     *
     * @var string
     */
    protected static $sender;

    /**
     * The subject line
     *
     * @var string
     */
    protected static $subjectLine;

    /**
     * Body text
     *
     * @var string
     */
    protected static $body;

    /**
     * @var array - BatchModel\Entity's toArray()
     */
    protected $batch;

    /**
     * @var array - Merchant\Entity's toArray()
     */
    protected $merchant;

    /**
     * Processed output batch file local path
     *
     * @var string
     */
    protected $outputFileLocalPath;

    public function __construct(
        array $batch,
        array $merchant,
        string $outputFileLocalPath)
    {
        parent::__construct();

        $this->batch               = $batch;
        $this->merchant            = $merchant;
        $this->outputFileLocalPath = $outputFileLocalPath;
    }

    protected function addSender()
    {
        $fromEmail  = Constants::MAIL_ADDRESSES[static::$sender];
        $fromHeader = Constants::HEADERS[static::$sender];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->merchant['transaction_report_email'];

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $this->subject(sprintf(static::$subjectLine, $today));

        return $this;
    }

    protected function addMailData()
    {
        $data = ['body' => static::$body];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->outputFileLocalPath);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, static::$mailTag);
        });

        return $this;
    }
}
