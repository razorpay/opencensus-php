<?php

namespace RZP\Mail\User;

use RZP\Models\User;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Otp extends Mailable
{
    /**
     * Holds addtiaionl input parameters per action.
     * E.g. account_number for create_payout action, gets used in blade file.
     * @var array
     */
    public $input;

    /**
     * @var array
     */
    public $user;

    /**
     * @var array
     */
    public $otp;

    /**
     * @var string
     */
    public $formattedAction;

    public function __construct(array $input, User\Entity $user, array $otp)
    {
        parent::__construct();

        $this->input  = $input;
        $this->user   = $user->toArrayPublic();
        $this->otp    = $otp;

        // E.g. 'verify contact', 'create payout' etc, used in blade file.
        $this->formattedAction = str_replace('_', ' ', $input['action']);
    }

    protected function addRecipients()
    {
        $this->to($this->user['email'], $this->user['name']);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject("RazorpayX | OTP to {$this->formattedAction}");

        return $this;
    }

    protected function addMailData()
    {
        $this->with(
            [
                'input'           => $this->input,
                'user'            => $this->user,
                'otp'             => $this->otp,
                'formattedAction' => $this->formattedAction,
            ]);

        return $this;
    }

    protected function addHtmlView()
    {
        // For specific action there might exist different blade file. Generic fallback is emails.user.otp.
        switch ($this->input['action'])
        {
            case 'create_payout':
                $view = 'emails.user.otp_create_payout';
                break;

            default:
                $view = 'emails.user.otp';
                break;
        }

        $this->view($view);

        return $this;
    }
}
