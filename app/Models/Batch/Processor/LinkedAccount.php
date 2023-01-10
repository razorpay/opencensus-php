<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Batch\Helpers\LinkedAccount as Helper;
use RZP\Models\Feature\Constants as FeatureConstants;

class LinkedAccount extends Base
{
    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * @var BankAccount\Core
     */
    protected $bankAccountCore;

    /**
     * @var MerchantDetail\Core
     */
    protected $merchantDetailCore;

    /**
     * @var Merchant\Service
     */
    protected $merchantService;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantCore       = new Merchant\Core;
        $this->bankAccountCore    = new BankAccount\Core;
        $this->merchantDetailCore = new MerchantDetail\Core;
        $this->merchantService    = new Merchant\Service;
    }

    protected function processEntry(array & $entry)
    {
        $this->merchantCore->blockLinkedAccountCreationIfApplicable($this->merchant);

        $account = $this->repo->transactionOnLiveAndTest(function () use (& $entry)
        {
            return $this->createOrUpdateAccountForEntry($entry);
        });

        $this->app->hubspot->trackLinkedAccountCreation($account->getEmail());
    }

    /**
     * @param  array $entry
     *
     * @return Merchant\Entity|Merchant\Account\Entity
     */
    protected function createOrUpdateAccountForEntry(array & $entry)
    {
        $status = Status::FAILURE;

        $accountId = $entry[Header::ACCOUNT_ID] ?: null;
        //
        // If account id exists in file row, just update the bank account
        // details. Assume that is the expected use case, must not edit/update
        // other things via batch flow.
        //
        if ($accountId !== null)
        {
            $account = $this->repo
                            ->merchant
                            ->findByAccountIdAndParent(
                                $accountId,
                                $this->merchant,
                                true);

            // Building input for bank core's method
            $buildInput = $this->bankAccountCore
                               ->buildBankAccountArrayFromMerchantDetail(
                                    $account->merchantDetail,
                                    true);
            $overriddenInput = Helper::getBankAccountInput($entry);
            $input = array_merge($buildInput, $overriddenInput);

            $this->repo->transactionOnLiveAndTest(function () use($input, $account, $accountId, &$status)
            {
                $this->bankAccountCore->createOrChangeBankAccount($input, $account, false, false);

                //penny testing changes when bank details are updated and feature flag enabled for parent merchant.
                if ($account->isFeatureEnabledOnParentMerchant(FeatureConstants::ROUTE_LA_PENNY_TESTING) === true)
                {
                    // putting funds on hold for this linked account until the new bank account is verified.
                    $account->setHoldFundsReason(Merchant\Constants::LINKED_ACCOUNT_PENNY_TESTING);

                    $this->repo->saveOrFail($account);

                    $onHoldFundsInput[Merchant\Entity::HOLD_FUNDS] = true;

                    Merchant\Account\Entity::verifyIdAndStripSign($accountId);

                    $this->merchantService->edit($accountId, $onHoldFundsInput);
                }
            });

            if ($account->isFeatureEnabledOnParentMerchant(FeatureConstants::ROUTE_LA_PENNY_TESTING) === true)
            {
                $this->merchantDetailCore->publicAttemptPennyTesting($account->merchantDetail, $account, true);

                $this->merchantDetailCore->publicTriggerValidationRequests($account, $account->merchantDetail);

                $this->repo->saveOrFail($account->merchantDetail);
            }
            $status = Status::SUCCESS;
        }
        else
        {
            $input = Helper::getSubMerchantInput($entry);
            $account = $this->merchantCore->createSubMerchant($input, $this->merchant);

            $detailInput = Helper::getSubMerchantDetailInput($entry);
            $response = $this->merchantDetailCore->saveMerchantDetails($detailInput, $account);

            $status = ($response['auto_activated'] === true) ?
                        Status::SUCCESS : Status::FAILURE;
        }

        // Append account ID to output fields
        $entry[Header::STATUS]     = $status;
        $entry[Header::ACCOUNT_ID] = Merchant\Account\Entity::getSignedId($account->getId());

        return $account;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
