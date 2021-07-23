<?php

namespace RZP\Mail\Reward;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class CouponCountThreshold extends Mailable
{
    protected $data;

    protected $templateName;

    protected $checkoutRewardsEmail = 'checkout-rewards@razorpay.com';

    protected $customSubject;

    public function __construct(string $subject, array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->customSubject = $subject;
    }

    protected function addRecipients()
    {
        $this->to($this->checkoutRewardsEmail);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
            Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addTextView()
    {
        $this->view("emails.reward.coupon_count_threshold");

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->customSubject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'subject'           => $this->data['subject'],
            'brand_name'        => $this->data['brand_name'],
            'advertiser_id'     => $this->data['advertiser_id'],
            'reward_id'         => $this->data['reward_id'],
            'reward_name'       => $this->data['reward_name'],
            'count'             => $this->data['count'],
            'generic_present'   => $this->data['generic_present']
        ];

        $this->with($data);

        return $this;
    }
}
