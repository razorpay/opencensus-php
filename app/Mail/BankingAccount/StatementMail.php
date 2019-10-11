<?php

namespace RZP\Mail\BankingAccount;

use Carbon\Carbon;
use RZP\Mail\Base\Mailable;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;

class StatementMail extends Mailable
{
    const HEADER = 'RX Account Statement';

    const DATE_FORMAT = 'd M y';

    const DATE_TIME_FORMAT = 'd M y h:i A';

    protected $data;

    protected $channel;

    protected $toDate;

    protected $fromDate;

    protected $merchant;

    protected $toEmails;

    protected $fileDownloadUrl;

    public function __construct(MerchantEntity $merchant,
                                array $toEmails,
                                string $fromDate,
                                string $toDate,
                                string $fileDownloadUrl)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->toEmails = $toEmails;

        $this->fromDate = $fromDate;

        $this->toDate = $toDate;

        $this->fileDownloadUrl = $fileDownloadUrl;
    }

    protected function addSubject()
    {
        $finalSubject = self::HEADER . ': ' . $this->getReadableDuration();

        $this->subject($finalSubject);

        return $this;
    }

    protected function format($timestamp, $format)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)
                     ->format($format);
    }

    protected function addRecipients()
    {
        $this->to($this->toEmails);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::REPORTS];

        $this->from($email);

        return $this;
    }

    protected function getReadableDuration()
    {
        $formattedFromDate = $this->format($this->fromDate, self::DATE_FORMAT);

        $formattedToDate = $this->format($this->toDate, self::DATE_FORMAT);

        return $formattedFromDate . ' - ' . $formattedToDate;
    }

    protected function addMailData()
    {
        $data = [
            'header'            => self::HEADER,
            'merchant_id'       => $this->merchant->getId(),
            'name'              => $this->merchant->getName(),
            'duration'          => $this->getReadableDuration(),
            'file_download_url' => $this->fileDownloadUrl,
            'generated_at'      => $this->format(Carbon::now()->getTimestamp(), self::DATE_TIME_FORMAT)
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.bank_account.statement');

        return $this;
    }
}
