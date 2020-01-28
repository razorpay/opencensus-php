<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class L2SubmissionGreylist extends Mailable
{
    const SUPPORT_URL    = 'https://x.razorpay.com/?support=ticket';

    const SUBJECT        = 'KYC Form submitted for Razorpay';

    const TEMPLATE_PATH  = 'emails.merchant.razorpayx.l2_submission_greylisted';

    protected $merchantId;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $this->merchantId = $merchantId;
    }

    protected function addMailData()
    {
        $data = [
            'support_url' => self::SUPPORT_URL
        ];

        $this->with($data);

        return $this;
    }

    protected function addRecipients()
    {
        $repo = App::getFacadeRoot()['repo'];

        $merchant = $repo->merchant->find($this->merchantId);

        $this->to($merchant->getEmail(),
                  $merchant->getName());

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }
}
