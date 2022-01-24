<?php

namespace RZP\Mail\Admin\Account;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Base\Utility;
use RZP\Models\Admin\Admin\Entity as AdminEntity;

class Otp extends Mailable
{

    /**
     * @var array
     */
    public $input;

    /**
     * @var array
     */
    public $admin;

    /**
     * @var array
     */
    public $otp;

    /**
     * @var String
     */
    public $orgBusinessName;

    protected $org;


    public function __construct(array $input, AdminEntity $admin, String $orgBusinessName, array $otp, $org)
    {
        parent::__construct();

        $this->input = $input;
        $this->admin  = $admin->toArrayPublic();
        $this->otp   = $otp;
        $this->orgBusinessName = $orgBusinessName;
        $this->org = $org;
    }

    protected function addRecipients()
    {
        $this->to($this->admin['email'], $this->admin['name']);

        return $this;
    }

    protected function addSender()
    {
        switch ($this->input['action'])
        {
            //in verify_email email goes from support support@razorpay.com
            case 'verify_2fa':
                $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

                $fromHeader = Constants::HEADERS[Constants::SUPPORT];

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
        $subject = "{$this->orgBusinessName} | OTP to {$this->getFormattedAction()}";

        switch ($this->input['action'])
        {

            case 'verify_2fa':
                $subject = "{$this->orgBusinessName} | OTP to {$this->getFormattedAction()}";
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
            $this->otp['expires_at'] = Carbon::createFromTimestamp(
                $this->otp['expires_at'],
                Timezone::IST)
                ->format('M d, 20y (g:i A)');

        }

        $this->with(
            [
                'input'            => $this->input,
                'user'             => $this->admin,
                'otp'              => $this->otp,
                'org'              => $this->org,
                'formatted_action' => $this->getFormattedAction(),
            ]);

        return $this;
    }

    protected function addHtmlView()
    {
        switch ($this->input['action'])
        {
            case 'verify_2fa':
                $view = 'emails.admin.otp';
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
