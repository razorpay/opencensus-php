<?php

namespace RZP\Mail\Payout;

use App;
use Carbon\Carbon;
use Symfony\Component\Mime\Email;


use RZP\Models\Merchant;
use RZP\Mail\Base\Mailable;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\Payout\Core;
use RZP\Models\Payout\Entity;

class PayoutProcessedContactCommunication extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout.processed_contact_communication';

    const SUBJECT = 'Ka-Ching! Payment Received from %s';

    const DATE_FORMAT = 'd M Y g:i A';

    const SUPPORT_URL          = 'support_url';

    const SUPPORT_CONTACT      = 'support_contact';

    const SUPPORT_EMAIL        = 'support_email';

    protected $payoutId;

    /** @var Entity $payout*/
    protected $payout;

    /** @var  Merchant\Entity $merchant */
    protected $merchant = null;

    protected $recipientEmail = null;

    protected $supportDetails = null;

    public function __construct(string $payoutId, string $recipientEmail, array $supportDetails)
    {
        parent::__construct();

        $this->payoutId = $payoutId;

        $this->setPayout();

        $this->setMerchant();

        $this->supportDetails = $supportDetails;

        $this->recipientEmail = $recipientEmail;
    }

    protected function setPayout()
    {
        if ($this->payout === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            try
            {
                $this->payout = $repo->payout->findOrFail($this->payoutId);

                if ($this->payout->getIsPayoutService() === true)
                {
                    $this->payout = (new Core)->getAPIModelPayoutFromPayoutService($this->payoutId);
                }
            }
            catch (\Throwable $exception)
            {
                $this->payout = (new Core)->getAPIModelPayoutFromPayoutService($this->payoutId);

                if (empty($this->payout) === true)
                {
                    throw $exception;
                }
            }
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
            'payout_narration'        => $this->payout->getNarration(),
            'merchant_website'        => $this->supportDetails[self::SUPPORT_URL] ?? '',
            'merchant_email'          => $this->supportDetails[self::SUPPORT_EMAIL] ?? '',
            'merchant_phone'          => $this->supportDetails[self::SUPPORT_CONTACT] ?? '',
            'customer_email'          => $this->recipientEmail,
            //'sent'                    => [
            //    'url' => sprintf('https://x.razorpay.com/payouts?id=%s', $this->payout->getPublicId()),
            //],
            'learn_more_url'             => 'https://razorpay.com/x'
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYOUT_SUCCESSFUL_CONTACT_MAIL);
        });

        return $this;
    }
}
