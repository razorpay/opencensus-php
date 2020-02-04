<?php

namespace RZP\Mail\Report\RazorpayX;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Invoice\Entity;
use RZP\Models\Report\Types\BankingInvoiceReport;

class MerchantBankingInvoice extends Mailable
{
    const EMAIL_VIEW            = 'emails.merchant.banking_invoice';
    const FILE_DOWNLOAD_URL     = 'file_download_url';
    const SUPPORT_TICKET_URL    = 'https://x.razorpay.com/settings/invoices?support=ticket';
    const MONTH                 = 'month';
    const AMOUNT                = 'amount';
    const YEAR                  = 'year';

    protected $fileDownloadUrl;

    protected $data;

    protected $merchant;

    protected $toAddresses;

    public function __construct(string $fileDownloadUrl, array $data, $toAddresses = null)
    {
        parent::__construct();

        $this->fileDownloadUrl = $fileDownloadUrl;

        $this->data = $data;

        $this->toAddresses = $toAddresses;
    }

    protected function addHtmlView()
    {
        return $this->view(self::EMAIL_VIEW);
    }

    protected function addMailData()
    {
        parent::addMailData();

        $this->data[self::FILE_DOWNLOAD_URL] = $this->fileDownloadUrl;

        return $this->with('month', $this->getMonthName($this->data[self::MONTH]))
                    ->with('year', $this->data[self::YEAR])
                    ->with('amount', $this->data[BankingInvoiceReport::ROWS]
                                                [BankingInvoiceReport::COMBINED]
                                                [BankingInvoiceReport::GRAND_TOTAL])
                    ->with('invoice_link', $this->data[self::FILE_DOWNLOAD_URL])
                    ->with('ticket', self::SUPPORT_TICKET_URL);
    }

    protected function addSender()
    {
        return $this->from(
            Constants::MAIL_ADDRESSES[Constants::NOREPLY],
            Constants::HEADERS[Constants::NOREPLY]
        );
    }

    protected function addReplyTo()
    {
        return $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT]);
    }

    protected function addRecipients()
    {
        return $this->to($this->toAddresses);
    }

    protected function addSubject()
    {
        $subject = 'Invoice for the month of ' .
                   $this->getMonthName($this->data[self::MONTH]) .
                   ' ' .
                   $this->data[self::YEAR] .
                   ' has been generated';

        return $this->subject($subject);
    }

    protected function getMonthName($monthNumber)
    {
        return date("F", mktime(0, 0, 0, $monthNumber, 1));
    }
}
