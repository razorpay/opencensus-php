<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class Base extends BaseCore
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $this->createTransaction($payout);

        $this->createFundTransferAttempt($payout, $ftaAccount);
    }

    public function processTransaction(Entity $payout)
    {
        return $this->createTransaction($payout);
    }

    public function processCreateFundTransferAttempt(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->createFundTransferAttempt($payout, $ftaAccount);
    }

    protected function createTransaction(Entity $payout)
    {
        list ($txn, $feeSplit) = (new Transaction\Processor\Payout($payout))->createTransaction();

        $this->saveCreatedTxn($payout, $txn, $feeSplit);
    }

    protected function createTransactionWithIdAndLedgerBalance(Entity $payout, string $txnId, int $balance)
    {
        list ($txn, $feeSplit) = (new Transaction\Processor\Payout($payout))->createTransactionWithIdAndLedgerBalance($txnId, $balance);

        return $this->saveCreatedTxn($payout, $txn, $feeSplit);
    }

    protected function createFundTransferAttempt(Entity $payout, $ftaAccount)
    {
        // For VA to VA transfers using creditTransfer entity we don't create FTA
        if ($payout->isVaToVaPayout() === true)
        {
            return;
        }

        $ftaInput = [
            FundTransferAttempt\Entity::PURPOSE   => $payout->getPurposeType(),
            FundTransferAttempt\Entity::CHANNEL   => $payout->getChannel(),
            FundTransferAttempt\Entity::MODE      => $payout->getMode(),
            FundTransferAttempt\Entity::NARRATION => $payout->getNarration(),
        ];

        $ftaCore = new FundTransferAttempt\Core;

        $ftaAccountEntity = $ftaAccount->getEntity();

        switch ($ftaAccountEntity)
        {
            case Constants\Entity::BANK_ACCOUNT:
                $bankAccount = $this->getBankAccountToAssociateWithFTA($ftaAccount, $payout);

                $ftaCore->createWithBankAccount($payout, $bankAccount, $ftaInput);
                break;

            case Constants\Entity::VPA:
                $ftaCore->createWithVpa($payout, $ftaAccount, $ftaInput);
                break;

            case Constants\Entity::CARD:
                $ftaInput = $this->modifyFTAInputForCARDModeIfRequired($ftaInput);

                $ftaCore->createWithCard($payout, $ftaAccount, $ftaInput);
                break;

            case Constants\Entity::WALLET_ACCOUNT:
                $ftaCore->createWithWalletAccount($payout, $ftaAccount, $ftaInput);
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'Payout fta destination entity is invalid. '. $ftaAccount->getEntity(),
                    [
                        'payout_id'             => $payout->getId(),
                        'fta_account_id'        => $ftaAccount->getId(),
                        'fta_account_entity'    => $ftaAccountEntity,
                    ]);
        }
    }

    protected function modifyFTAInputForCARDModeIfRequired(array $ftaInput)
    {
        if ((isset($ftaInput[FundTransferAttempt\Entity::MODE]) === true) and
            ($ftaInput[FundTransferAttempt\Entity::MODE] === Mode::CARD))
        {
            $ftaInput[FundTransferAttempt\Entity::MODE] = Mode::CT;
        }

        return $ftaInput;
    }

    protected function postTransactionCreationProcessing(Transaction\Entity $txn, Entity $payout)
    {
        return;
    }

    protected function getBankAccountToAssociateWithFTA(PublicEntity $bankAccount, Entity  $payout)
    {
        // We want to swap older IFSC to new IFSC for banks which are getting
        // merged to bigger banks. This is being done for now for IMPS payouts
        // only. This code will remain in FTA currently and will have to ported
        // to payouts as bank mergers will continue to happen.
        // Detailed discussion - https://razorpay.slack.com/archives/CM9230B5Y/p1606721863218100
        $ifscCode = $bankAccount->getIfscCode();

        $merchant = $payout->merchant;

        if ($this->isIfscSwappingRequired($ifscCode) === true)
        {
            $ifscCode = $this->getNewIfscMapping($bankAccount->getIfscCode());

            // only below parameters are required for FA payouts
            $input = [
                BankAccount\Entity::IFSC_CODE        => $ifscCode,
                BankAccount\Entity::ACCOUNT_NUMBER   => $bankAccount->getAccountNumber(),
                BankAccount\Entity::BENEFICIARY_NAME => $bankAccount->getBeneficiaryName(),
                BankAccount\Entity::TYPE             => $bankAccount->getType(),
                BankAccount\Entity::ENTITY_ID        => $bankAccount->getEntityId(),
            ];

            $existingBankAccount = $this->repo->bank_account->fetchBankAccount(
                $merchant,
                $input);

            if ($existingBankAccount !== null)
            {
                $this->trace->info(TraceCode::EXISTING_BANK_ACCOUNT_FOUND,
                                   [
                                       'bank_account_id' => $existingBankAccount->getId(),
                                   ]);

                return $existingBankAccount;
            }

            // The below fields will be filled when bank account gets associated to its source
            unset($input[BankAccount\Entity::TYPE]);
            unset($input[BankAccount\Entity::ENTITY_ID]);

            $bankAccount = (new BankAccount\Core)->createBankAccountForSource($input,
                                                                              $merchant,
                                                                              $bankAccount->source,
                                                                              "add_bank_account");

            $this->trace->info(TraceCode::BANK_ACCOUNT_CREATED,
                               [
                                   'bank_account_id' => $bankAccount->getId(),
                               ]);
        }

        return $bankAccount;
    }

    protected function isIfscSwappingRequired(string $ifsc)
    {
        $isRequired = false;

        if (array_key_exists($ifsc, BankAccount\OldNewIfscMapping::$oldToNewIfscMapping) === true)
        {
            $isRequired = true;
        }

        return $isRequired;
    }

    protected function getNewIfscMapping(string $ifsc)
    {
        $newIfsc = BankAccount\OldNewIfscMapping::getNewIfsc($ifsc);

        $this->trace->info(TraceCode::BANK_ACCOUNT_OLD_TO_NEW_IFSC_BEING_USED, [
                  'old_ifsc' => $ifsc,
                  'new_ifsc' => $newIfsc,
        ]);

        return $newIfsc;
    }

    /**
     * @param Entity $payout
     * @param $txn
     * @param $feeSplit
     * @throws Exception\LogicException
     */
    protected function saveCreatedTxn(Entity $payout, $txn, $feeSplit)
    {
        $payout->setFees($txn->getFee());
        $payout->setTax($txn->getTax());

        // In case of high TPS composite API merchants, we calculate this much earlier
        // and we wish to use the existing values itself.
        if ($payout->isBalancePreDeducted() === true) {
            /** @var Transaction\Entity $txn */
            $transactionId = $payout->getTransactionIdWhenBalancePreDeducted();
            $transactionCreatedAt = $payout->getTransactionCreatedAtWhenBalancePreDeducted();
            $closingBalance = $payout->getClosingBalanceWhenBalancePreDeducted();

            $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_SETTING_TRANSACTION_DETAILS,
                [
                    'payout_id' => $payout->getId(),
                    'closing_balance' => $closingBalance,
                    'transaction_id' => $transactionId,
                    'transaction_created_at' => $transactionCreatedAt,
                ]);

            if (($transactionId !== '') and
                ($transactionCreatedAt !== 0)) {
                $txn->setId($transactionId);
                $txn->setCreatedAt($transactionCreatedAt);
                $txn->setBalance($closingBalance);
                $payout->transaction()->associate($txn);
            }
        }

        $this->repo->saveOrFail($txn);

        if ($payout->isBalancePreDeducted() === false) {
            // We are skipping saving the fees breakup for high TPS composite API merchants.
            (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);
        }

        $this->postTransactionCreationProcessing($txn, $payout);

        return $txn;
    }
}
