<?php

namespace RZP\Mail\User;

use RZP\Mail\Base;
use RZP\Models\User;
use RZP\Constants\Product;
use RZP\Mail\Base\EmailHelper;

class PasswordChange extends Base\Mailable
{
    protected $org;

    /**
     * @var User\Entity
     */
    protected $user;

    protected $product;

    protected $passwordChangedAt;

    public function __construct(User\Entity $user, $org, $changedAt, $product = Product::PRIMARY)
    {
        parent::__construct();

        $this->user = $user->toArrayPublic();

        $this->passwordChangedAt = $changedAt;

        $this->org = $org;

        $this->product = $product;
    }

    protected function addRecipients()
    {
        $email = $this->user['email'];

        $name = $this->user['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSender()
    {
        $this->from($this->org['from_email'], $this->org['display_name']);

        return $this;
    }

    protected function addSubject()
    {
        $orgName = $this->org['display_name'];

        $subject = sprintf("Password changed for your %s account", $orgName);

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'org'        => $this->org,
            'changed_at' => $this->passwordChangedAt,
            'product'    => $this->product,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.user.password_change');

        return $this;
    }

    public function shouldSendEmailViaStork(): bool
    {
        return  (new EmailHelper)->isStorkSupportedCheckViaSplitz($this->user['id'], $this->org['id'], 'password_change') ?? false;
    }

    public function getParamsForStork(): array
    {
        $data = 
        [
            'org'        => $this->org,
            'changed_at' => $this->passwordChangedAt,
            'product'    => $this->product,
        ];

        $storkParams = 
        [
            'template_name' => 'banking_mail_password_change',
            'template_namespace' => 'payments_banking',
            'org_id'             => $this->org['id'],
            'params' => $data,
        ];
        $storkParams['params']['resetPasswordUrl'] = 'https://' . $this->org['hostname'] .'/#/access/forgotpwd';
        $storkParams['params']['contactUsUrl'] = 'https://' . $this->org['hostname'] .'/#/app/dashboard#request';

        return $storkParams;
    }
}

