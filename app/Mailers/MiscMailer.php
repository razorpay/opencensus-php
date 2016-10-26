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

        return $this;
    }
}
