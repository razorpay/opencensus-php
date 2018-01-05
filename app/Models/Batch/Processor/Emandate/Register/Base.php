<?php

namespace RZP\Models\Batch\Processor\Emandate\Register;

use RZP\Exception;
use RZP\Models\Customer\Token;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    protected function processEntry(array & $entry)
    {
        //
        // Expects $parsedData to have keys
        // 'token_id'       : Corresponds to Token\Entity::ID
        // 'status'         : Corresponds to Token\Entity::RECURRING_STATUS
        // 'remark'         : Corresponds to Token\Entity::RECURRING_FAILURE_REASON
        // 'account_number' : Corresponds to Token\Entity::ACCOUNT_NUMBER
        //
        $parsedData = $this->getDataFromRow($entry);

        $tokenId = $parsedData['token_id'];

        $accountNumber = $parsedData['account_number'];

        $remark = $parsedData['remark'];

        $token = $this->repo->token->getTokenByIdAndAccountNumber($tokenId, $accountNumber);

        $currentRecurringStatus = $token->getRecurringStatus();

        $parsedStatus = $parsedData['status'];

        if (Token\RecurringStatus::isFinalStatus($currentRecurringStatus) === true)
        {
            if ($currentRecurringStatus !== $parsedStatus)
            {
                throw new Exception\LogicException(
                    'Token status mismatch: current_status: ' . $currentRecurringStatus . ', parsed_status: ' . $parsedStatus);
            }

            // If the token has already been updated with the correct value
            return;
        }

        $tokenParams = [
            Token\Entity::RECURRING_STATUS          => $parsedStatus,
            Token\Entity::RECURRING_FAILURE_REASON  => $remark,
        ];

        (new Token\Core)->updateTokenFromNetbankingGatewayData($token, $tokenParams);

        $this->repo->saveOrFail($token);
    }

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    /**
     * Child class must implement it
     *
     * @param array $entry
     */
    protected function getDataFromRow(array & $entry)
    {
        throw new \BadMethodCallException();
    }

    protected function createSetOutputFileAndSave(array & $entries)
    {
        return;
    }

    protected function sendProcessedMail()
    {
        return;
    }
}