<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\Merchant\Entity as MerchantEntity;

class FundLoadingFailed extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.merchant.razorpayx.fund_loading_failed';

    const URL = "https://razorpay.com/docs/razorpayx/announcements/source-account-validation";

    const SUBJECT = 'Fund loading of %s to your RazorpayX account number %s has been rejected';

    protected $bankTransferId = null;

    protected $merchantId = null;

    protected $bankTransfer = null;

    protected $merchant = null;

    public function __construct(string $bankTransferId, string $actualMerchantId)
    {
        parent::__construct();

        $this->bankTransferId = $bankTransferId;

        $this->merchantId = $actualMerchantId;
    }

    protected function addRecipients()
    {
        $merchant = $this->getMerchant();

        $recipients = $merchant->getTransactionReportEmail();

        $this->to($recipients);

        return $this;
    }

    protected function getBankTransfer(): Entity
    {
        if ($this->bankTransfer === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->bankTransfer = $repo->bank_transfer->findOrFail($this->bankTransferId);
        }

        return $this->bankTransfer;
    }

    protected function getMerchant(): MerchantEntity
    {
        if ($this->merchant === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->merchant = $repo->merchant->findOrFail($this->merchantId);
        }

        return $this->merchant;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT], Constants::HEADERS[Constants::RAZORPAY_X]);
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $bankTransfer = $this->getBankTransfer();

        $formattedAmount = $bankTransfer->getFormattedAmountsAsPerCurrency('INR', $bankTransfer->getAmount());

        $maskedAccountNumber = mask_except_last4($bankTransfer->getPayeeAccount());

        $subject = sprintf(self::SUBJECT, $formattedAmount, $maskedAccountNumber);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $bankTransfer = $this->getBankTransfer();

        $formattedAmount = $bankTransfer->getFormattedAmountsAsPerCurrency('INR', $bankTransfer->getAmount());

        $data = [
            'payer_account_number'  => mask_except_last4($bankTransfer->getPayerAccount()),
            'payee_account_number'  => mask_except_last4($bankTransfer->getPayeeAccount()),
            'utr'                   => $bankTransfer->getUtr(),
            'payer_ifsc'            => $bankTransfer->getPayerIfsc(),
            'amount'                => $formattedAmount,
            'url'                   => self::URL
        ];

        $this->with($data);

        return $this;
    }
}
