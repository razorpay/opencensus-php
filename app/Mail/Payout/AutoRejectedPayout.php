<?php

namespace RZP\Mail\Payout;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Payout\Entity;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Entity as MerchantEntity;

class AutoRejectedPayout extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout.auto_rejected';

    const SUBJECT = 'Scheduled Payout <%s> for %s worth %s has been auto rejected';

    protected $payoutId;

    protected $payout = null;

    protected $merchant = null;

    public function __construct(string $payoutId)
    {
        parent::__construct();

        $this->payoutId = $payoutId;
    }

    protected function addRecipients()
    {
        $merchant = $this->getPayout()->merchant;

        $recipients = $merchant->getTransactionReportEmail();

        $this->to($recipients);

        return $this;
    }

    protected function getPayout(): Entity
    {
        if ($this->payout === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->payout = $repo->payout->findOrFail($this->payoutId);
        }

        return $this->payout;
    }

    protected function getMerchant(): MerchantEntity
    {
        return $this->getPayout()->merchant;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                           Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $payout = $this->getPayout();

        $subject = sprintf(self::SUBJECT,
                           $payout->getPublicId(),
                           $payout->getFormattedScheduledFor(),
                           $payout->getFormattedAmount()
        );

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $payout = $this->getPayout();

        [$amountSymbol, $rupeesAmount, $paiseAmount] = $this->payout->getAmountComponents();

        $accountType = 'RazorpayX account';

        if ($this->payout->getBalanceAccountType() === Balance\AccountType::DIRECT)
        {
            $accountType = 'RBL Current Account';
        }

        $data = [
            'amount'        => [
                $amountSymbol,
                $rupeesAmount,
                $paiseAmount
            ],
            'payout_id'     => $payout->getPublicId(),
            'scheduled_for' => $payout->getFormattedScheduledFor(),
            'account_no'    => $payout->getAccountNumberAttribute(),
            'account_type'  => $accountType,
            'sent'          => [
                'url'       => sprintf('https://x.razorpay.com/payouts?id=%s', $payout->getPublicId()),
            ],
            'support_url'   => 'https://x.razorpay.com/?support=ticket'
        ];

        $this->with($data);

        return $this;
    }
}
