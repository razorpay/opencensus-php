<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Models\User\Entity as UserEntity;

class PayoutApproval extends Base
{
    const APPROVED_COUNT    = 'approved_count';
    const REJECTED_COUNT    = 'rejected_count';
    const APPROVED_AMOUNT   = 'approved_amount';
    const REJECTED_AMOUNT   = 'rejected_amount';

    const APPROVED_PAYOUT   = 'A';
    const REJECTED_PAYOUT   = 'R';

    protected function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $response = parent::getValidatedEntriesStatsAndPreview($entries);

        //Get additional stats
        // Approved count, amount
        // Rejected count, amount
        $approvedEntries = array_filter($entries, function($entry)
        {
            return ($entry[Header::APPROVE_REJECT_PAYOUT] === self::APPROVED_PAYOUT);
        });

        $totalApprovedAmount = array_sum(array_column($approvedEntries, Header::P_A_AMOUNT));

        $rejectedEntries = array_filter($entries, function($entry)
        {
            return ($entry[Header::APPROVE_REJECT_PAYOUT] === self::REJECTED_PAYOUT);
        });

        $totalRejectedAmount = array_sum(array_column($rejectedEntries, Header::P_A_AMOUNT));

        $response += [self::APPROVED_COUNT   => count($approvedEntries),
                      self::REJECTED_COUNT   => count($rejectedEntries),
                      self::APPROVED_AMOUNT  => $totalApprovedAmount,
                      self::REJECTED_AMOUNT  => $totalRejectedAmount ];

        return $response;
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }

    /**
     * Adds the user email from basicAuth
     * @param $input
     */
    public function addSettingsIfRequired(& $input)
    {
        if(isset($input[Batch\Entity::CONFIG]) === false)
        {
            $input[Batch\Entity::CONFIG] = [];
        }

        /** @var UserEntity $user */
        $user = $this->app['basicauth']->getUser();

        if(empty($user) === false)
        {
            $input[Batch\Entity::CONFIG][UserEntity::EMAIL] = $user->getEmail();
        }
    }

}
