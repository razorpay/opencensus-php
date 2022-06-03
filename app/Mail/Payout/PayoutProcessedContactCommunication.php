<?php

namespace RZP\Mail\Payout;

use App;

use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Mail\Base\Mailable;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Payout\Entity;

class PayoutProcessedContactCommunication extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout.processed_contact_communication';

    const SUBJECT = '[Notification] %s has successfully transferred to you.';

    const DATE_FORMAT = 'd M Y g:i A';

    protected $payoutId;

    /** @var Entity $payout*/
    protected $payout;

    /** @var  Merchant\Entity $merchant */
    protected $merchant = null;

    protected $recipientEmail = null;

    public function __construct(string $payoutId, string $recipientEmail)
    {
        parent::__construct();

        $this->payoutId = $payoutId;

        $this->setPayout();

        $this->setMerchant();

        $this->recipientEmail = $recipientEmail;
    }

    protected function setPayout()
    {
        if ($this->payout === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->payout = $repo->payout->findOrFail($this->payoutId);
        }
    }

    protected function setMerchant()
    {
        if ($this->payout !== null)
        {
            $this->merchant = $this->payout->merchant;
        }
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->recipientEmail);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
                           Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {

        $subject = sprintf(self::SUBJECT,
                           $this->merchant->getBillingLabel()
        );

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        [$amountSymbol, $rupeesAmount, $paiseAmount] = $this->payout->getAmountComponents();

        $data = [
            'payout_amount'                  => [
                $amountSymbol,
                $rupeesAmount,
                $paiseAmount
            ],
            'merchant_name'           => $this->merchant->getName(),
            'merchant_billing_label'  => $this->merchant->getBillingLabel(),
            'merchant_brand_logo'     => $this->merchant->getFullLogoUrlWithSize()?? "https://saransh.dev/myAvatar.png",
            'merchant_brand_color'    => $this->merchant->getBrandColorElseDefault(),
            'merchant_contrast_color' => $this->merchant->getContrastOfBrandColor(),
            'payout_status'           => $this->payout->getStatus(),
            'payout_utr'              => $this->payout->getUtr() ?? '',
            'payout_reference_id'     => $this->payout->getReferenceId(),
            'payout_mode'             => $this->payout->getMode(),
            'payout_id'               => $this->payout->getPublicId(),
            'payout_processed_at'     => Carbon::createFromTimeStamp($this->payout->getProcessedAt(), Timezone::IST)
                                               ->format(self::DATE_FORMAT),
            'payout_notes'            => $this->payout->getNotes()->toArray(),
            'merchant_website'        => $this->merchant->merchantDetail->getWebsite() ?? '',
            'merchant_email'          => $this->merchant->merchantDetail->getContactEmail() ?? '',
            'merchant_phone'          => $this->merchant->merchantDetail->getContactMobile() ?? '',
            'customer_email'          => $this->recipientEmail,
            //'sent'                    => [
            //    'url' => sprintf('https://x.razorpay.com/payouts?id=%s', $this->payout->getPublicId()),
            //],
            'learn_more_url'             => 'https://razorpay.com/x'
        ];

        $this->with($data);

        return $this;
    }
}
