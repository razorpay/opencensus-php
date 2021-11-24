<?php

namespace RZP\Mail\User;

use Carbon\Carbon;
use RZP\Mail\Base\Mailable;
use RZP\Models\Base\Utility;
use RZP\Mail\Base\Constants;

class OtpSignup extends Mailable
{

    /**
     * @var array
     */
    public $input;

    /**
     * @var array
     */
    public $otp;

    public function __construct(array $input, array $otp)
    {
        parent::__construct();

        $this->input    = $input;
        $this->otp      = $otp;
    }

    protected function addRecipients()
    {
        $recipentEmail = $this->input['email'];

        $this->to($recipentEmail);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $this->replyTo($email);

        return $this;
    }

    protected function addSender()
    {
        $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $senderName  = Constants::HEADERS[Constants::NOREPLY];

        $this->from($senderEmail, $senderName);

        return $this;
    }


    protected function addSubject()
    {
        $this->subject('Razorpay | OTP to sign up');

        return $this;
    }

    protected function addMailData()
    {
        //converting to IST
        if(isset($this->otp['expires_at']) === true)
        {
            $this->otp['expires_at'] = $this->otp['expires_at'] + 19800;
        }

        $this->with(
            [
                'input'            => $this->input,
                'otp'              => $this->otp,
                'formatted_action' => $this->getFormattedAction(),
            ]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.otp_signup');

        return $this;
    }

    protected function getFormattedAction(): string
    {
        // E.g. 'Verify Contact', 'Create Payout' etc, used in blade file.
        return ucwords(str_replace('_', ' ', $this->input['action']));
    }
}
