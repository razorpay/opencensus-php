<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\User\Entity as UserEntity;

class PayoutApproval extends Base
{
    protected static $mailTag     = MailTags::PAYOUT_APPROVAL;

    protected static $sender      = Constants::NOREPLY;

    protected static $subjectLine = 'RazorpayX | Payout Approval Result File dated %s';

    protected static $body        = 'Please find attached processed payouts file.';

    /**
     * @return $this|Base|PayoutApproval
     */
    protected function addRecipients()
    {
        $userEmail = $this->batchSettings[UserEntity::EMAIL];

        if(empty($userEmail) === false)
        {
            $this->to($userEmail);
        }

        return $this;
    }
}
