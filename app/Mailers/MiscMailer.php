<?php

namespace App\Mailers;

class MiscMailer extends Mailer
{
    /**
     * For team member invitations
     */

    public function sendMemberInvitationEmail($invitation, $loggedInUser)
    {
        $invitation_array = $invitation->makeVisible('token')->toArray();

        $invitation_array['merchant'] = $invitation->merchant->toArray();

        $this->view = $invitation_array['user_id'] ? 'emails.invitations.existing' : 'emails.invitations.new';

        $this->data = compact('invitation_array', 'loggedInUser');

        $this->email = $invitation->email;
        $this->subject = 'Invitation to join a team | Razorpay';
        $this->mailTag = MailTags::MEMBER_INVITATION_MAIL;

        return $this;
    }

    public function sendForgetPasswordEmail($email, $org, $token, $expiryTime)
    {
        $this->view = 'emails.auth.reminder';

        $this->data = ['token' => $token, 'org' => $org, 'expiryTime' => $expiryTime];

        $this->email = $email;

        $this->subject = 'Razorpay - Password Reset Request';

        return $this;
    }
}
