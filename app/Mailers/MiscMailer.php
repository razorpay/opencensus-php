<?php

namespace App\Mailers;

class MiscMailer extends Mailer
{
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
