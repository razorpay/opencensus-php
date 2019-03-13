<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Contact as ContactModel;
use RZP\Models\FundAccount as FundAccountModel;

class FundAccount extends Base
{
    /**
     * @var FundAccountModel\Core
     */
    protected $fundAccountCore;

    /**
     * @var Contact
     */
    protected $contactProcessor;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->fundAccountCore = new FundAccountModel\Core;

        $this->contactProcessor = new Contact($batch);
    }

    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $this->repo->transaction(function() use (& $entry)
        {
            $fundAccount = $this->processEntryAndGetEntity($entry);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
            $entry[Batch\Header::FUND_ACCOUNT_ID] = $fundAccount->getPublicId();
        });
    }

    /**
     * Processes fund account entry but little differently when it is coming
     * from payout type batch flow. Note that there is a cascading design like
     * processor of payout type -> uses fund account's -> uses contact's. Though
     * contact type batch is disabled.
     *
     * When from payout type batch we check first existence of fund account
     * "ignoring" contact details in the entry. If it exists use that. Else fall
     * to the normal flow i.e. processEntryAndGetEntity(), refer that method.
     *
     * @param  array $entry
     * @return FundAccountModel\Entity
     */
    public function processEntryForPayoutBatch(array & $entry): FundAccountModel\Entity
    {
        $fundAccountInput = Batch\Helpers\FundAccount::getFundAccountInput($entry);
        $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetails($fundAccountInput, $this->merchant);

        if ($fundAccount !== null)
        {
            return $fundAccount;
        }

        // Instead of now calling processEntryAndGetEntity() rewrites few lines
        // here to avoid double query for fund account similarity check.

        $contact = $this->processEntryForContact($entry);

        return $this->fundAccountCore->create($fundAccountInput, $this->merchant, $contact);
    }

    public function processEntryAndGetEntity(array & $entry): FundAccountModel\Entity
    {
        $contact = $this->processEntryForContact($entry);

        return $this->processEntryForFundAccountForContact($entry, $contact);
    }

    protected function processEntryForContact(array & $entry): ContactModel\Entity
    {
        if (empty($entry[Batch\Header::CONTACT_ID]) === false)
        {
            return $this->repo->contact->findByPublicIdAndMerchant($entry[Batch\Header::CONTACT_ID], $this->merchant);
        }
        else
        {
            return $this->contactProcessor->processEntryAndGetEntity($entry);
        }
    }

    protected function processEntryForFundAccountForContact(
        array & $entry,
        ContactModel\Entity $contact): FundAccountModel\Entity
    {
        $input = Batch\Helpers\FundAccount::getFundAccountInput($entry, $contact);

        $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetails($input, $this->merchant, $contact);

        return $fundAccount ?: $this->fundAccountCore->create($input, $this->merchant, $contact);
    }
}
