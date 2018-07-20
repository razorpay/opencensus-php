<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base\Common;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\User\PasswordReset;
use RZP\Models\User\Entity as User;

class CreateSubMerchantAffiliate extends Mailable
{
    /**
     * @var array
     */
    protected $subMerchant;

    /**
     * @var array
     */
    protected $aggregator;

    /**
     * @var array
     */
    protected $org;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var int
     */
    protected $expiryTime;

    public function __construct(array $subMerchant, array $aggregator, array $org, User $user = null)
    {
        parent::__construct();

        $this->subMerchant = $subMerchant;

        $this->aggregator = $aggregator;

        $this->org = $org;

        if (empty($user) === false)
        {
            list($this->token, $this->expiryTime) = (new PasswordReset($user, $org))->getTokenAndExpiry();
        }
    }

    protected function addRecipients()
    {
        $email = $this->subMerchant['email'];

        $name = $this->subMerchant['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->aggregator['name'] . ' has added you as merchant');

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'merchant'    => $this->aggregator,
            'subMerchant' => $this->subMerchant,
            'token'       => $this->token,
            'expiryTime'  => $this->expiryTime,
            'org'         => $this->org,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::AFFILIATE_ADDED);
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.add_sub_merchant_affiliate');

        return $this;
    }
}
