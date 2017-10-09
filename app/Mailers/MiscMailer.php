<?php

namespace App\Mailers;

class MiscMailer extends Mailer
{
    public function sendForgetPasswordEmail($email, $org, $token, $expiryTime)
    {
        $this->view = 'emails.auth.reminder';

        $this->data = [
                        'token'      => $token,
                        'org'        => $org,
                        'expiryTime' => $expiryTime,
                        'email'      => urlencode($email),
                    ];

        $this->email = $email;

        $this->subject = 'Razorpay - Password Reset Request';
        $this->mailTag = MailTags::PASSWORD_RESET_REQUEST;

        return $this;
    }

    public function sendFeedbackToSupport($fromEmail, $subject, $message)
    {
        $this->view = 'emails.submitfeedback';

        $this->fromEmail = $fromEmail;

        $this->email = 'support@razorpay.com';

        $this->subject = $subject;

        $this->data = [
            'feedback'   =>  $message
        ];

        $this->mailTag = MailTags::FEEDBACK_MAIL;

        return $this;
    }
}
