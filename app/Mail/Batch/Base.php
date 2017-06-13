<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Models\Batch as BatchModel;

class Base extends Mailable
{
    /**
     * Map of mail tags per type of batch
     */
    const MAIL_TAGS_PER_TYPE = [
        BatchModel\Type::REFUND       => MailTags::BATCH_REFUNDS_FILE,
        BatchModel\Type::PAYMENT_LINK => MailTags::BATCH_PAYMENT_LINK_FILE,
    ];

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

    protected function addRecipients()
    {
        $emails = $this->merchant['transaction_report_email'];

        $this->to($emails);

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

            $tag = self::MAIL_TAGS_PER_TYPE[$this->batch[BatchModel\Entity::TYPE]];

            $headers->addTextHeader(MailTags::HEADER, $tag);
        });

        return $this;
    }
}
