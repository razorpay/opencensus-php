<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Trace\TraceCode;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class L2SubmissionGreylist extends Mailable
{
    const SUPPORT_URL    = '';

    const SUBJECT        = 'KYC Form submitted for Razorpay';

    const TEMPLATE_PATH  = 'emails.merchant.razorpayx.l2_submission_greylisted';

    protected $merchant;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $this->merchant = $repo->merchant->find($merchantId);
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
        $this->to($this->merchant->getEmail(),
                  $this->merchant->getName());

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
