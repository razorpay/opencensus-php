<?php

namespace RZP\Mail\User;

use Carbon\Carbon;

use RZP\Models\User;
use RZP\Mail\Base\Mailable;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Base\Utility;
use RZP\Exception\LogicException;

class Otp extends Mailable
{
    /**
     * Holds additional input parameters per action.
     * E.g. account_number for create_payout action, gets used in blade file.
     *
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

    public function __construct(array $input, User\Entity $user, array $otp)
    {
        parent::__construct();

        $this->input = $input;
        $this->user  = $user->toArrayPublic();
        $this->otp   = $otp;
    }

    protected function addRecipients()
    {
        $this->to($this->user['email'], $this->user['name']);

        return $this;
    }

    protected function addSender()
    {
        switch ($this->input['action'])
        {
            //in verify_email email goes from support support@razorpay.com
            case 'verify_email':
                $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

                $fromHeader = Constants::HEADERS[Constants::SUPPORT];

                $this->from($fromEmail, $fromHeader);
                break;
            case 'x_verify_email':
                $fromEmail = Constants::MAIL_ADDRESSES[Constants::X_SUPPORT];

                $fromHeader = Constants::HEADERS[Constants::X_SUPPORT];

                $this->from($fromEmail, $fromHeader);
                break;
            default:
                $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);
        }

        return $this;
    }

    protected function addSubject()
    {
        // Generic fall back subject.
        $subject = "Razorpay | OTP to {$this->getFormattedAction()}";

        switch ($this->input['action'])
        {
            case 'create_payout':
                if (isset($this->input['contact']) === true)
                {
                    $subject = sprintf(
                        "OTP for payout amount INR %s to %s generated at %s IST",
                        amount_format_IN($this->input['amount']),
                        str_limit($this->input['contact']['name'], 10),
                        Carbon::now(Timezone::IST)->format('d-M (D), h:i A'));
                }
                break;

            case 'verify_email':
                $subject = "Razorpay | OTP to {$this->getFormattedAction()}";
                break;

            case 'login_otp':
                $subject = "Razorpay | OTP to login";
                break;
            case 'verify_user':
                $subject = "Razorpay | OTP to verify email";
                break;
            case 'x_verify_email':
                $subject = "Verify your Email for RazorpayX";
                break;
        }

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {

        //converting to IST
        if(isset($this->otp['expires_at']) === true)
        {
            $this->otp['expires_at'] = $this->otp['expires_at'] + 19800;
        }
        //in verify_email and x_verify_email they need only the remaining minute for otp to expire so subtracting IST epoch and current time
        if ((isset($this->otp['expires_at']) === true) and (isset($this->input['action']) === true) and ($this->input['action'] === 'verify_email' || $this->input['action'] === 'x_verify_email'))
        {
            $diffTime = $this->otp['expires_at'] - Carbon::now()->timestamp - 19800;

            $this->otp['expires_at'] = Utility::getTimestampFormatted($diffTime, 'i');
        }

        $this->with(
            [
                'input'            => $this->input,
                'user'             => $this->user,
                'otp'              => $this->otp,
                'formatted_action' => $this->getFormattedAction(),
            ]);

        return $this;
    }

    protected function addHtmlView()
    {
        switch ($this->input['action'])
        {
            case 'create_payout':
                $view = 'emails.user.otp_create_payout';
                break;
            case 'create_payout_batch':
                $view = 'emails.user.otp_create_payout_batch';
                break;
            case 'create_payout_batch_v2':
                $view = 'emails.user.otp_create_payout_batch';
                break;

            case 'verify_email':
                $view = 'emails.user.otp_email_verify';
                break;
            case 'bulk_payout_approve':
                $view = 'emails.user.otp_bulk_payout_approve';
                break;
            case 'create_bulk_payout_link':
                $view = 'emails.user.otp_create_bulk_payout_link';
                break;
            case 'login_otp':
                $view = 'emails.user.otp_login';
                break;
            case 'verify_user':
                $view = 'emails.user.verify_user';
                break;
            case 'x_verify_email':
                $view = 'emails.user.razorpayx.otp_email_verify';
                break;
            case 'update_workflow_config':
            case 'delete_workflow_config':
            case 'create_workflow_config':
                $view = 'emails.user.otp_workflow_config';
                break;
            // Generic fall back template.
            default:
                $view = 'emails.user.otp';
                break;
        }

        $this->view($view);

        return $this;
    }

    protected function getFormattedAction(): string
    {
        // E.g. 'Verify Contact', 'Create Payout' etc, used in blade file.
        return ucwords(str_replace('_', ' ', $this->input['action']));
    }
}
