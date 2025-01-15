<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Ledger\ReverseShadow\IRCTCPayout\Core as IRCTCPayoutReverseShadowCore;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Feature\Constants as FeatureConstants;
use \RZP\Models\Ledger\Constants as LedgerConstants;

class Base extends BaseCore
{
    const FTA_MUTEX_LOCK_TIMEOUT = 30;

    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        // If the merchant is IRCTC and the PG_LEDGER_REVERSE_SHADOW feature is enabled, FTA and Txn will be
        // created later at the dual write worker.

        $irctcPayoutReverseShadowCore = new IRCTCPayoutReverseShadowCore();

        if ($irctcPayoutReverseShadowCore->isIrctcPGLedgerReverseShadowEnabled($payout->merchant)) {
                return;
        }
        else {
            $this->createTransaction($payout);

            $this->createFundTransferAttempt($payout, $ftaAccount);
        }
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

    protected function createFundTransferAttemptProcess(Entity $payout, $ftaAccount)
    {

        $ftaCreateStartTime = millitime();

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

        $ftaCreateEndTime = millitime();

        $this->trace->info(
            TraceCode::PAYOUT_FTA_ENTITY_CREATE_DURATION,
            [
                'payout_id'         => $payout->getId(),
                'merchant_id'       => $payout->getMerchantId(),
                'fta_creation_time' => $ftaCreateEndTime - $ftaCreateStartTime,
            ]);
    }

    public function createFundTransferAttempt(Entity $payout, $ftaAccount)
    {

        // For VA to VA transfers using creditTransfer entity we don't create FTA
        if ($payout->isVaToVaPayout() === true)
        {
            return;
        }

        // Check if there exists an FTA entity for this source id, if it does then return
        $fta = $this->repo->fund_transfer_attempt->getAttemptBySourceId($payout->getId(),Entity::PAYOUT);

        if (empty($fta) === false) {
            $this->trace->info(
                TraceCode::FTA_ENTITY_ALREADY_EXISTS,
                [
                    'fta_id'    => $fta->getId(),
                    'payout_id' => $payout->getId(),
                ]);

            return;
        }

        $mutexKey = 'fta_create_mutex_key_'.$payout->getId();

        return $this->app['api.mutex']->acquireAndRelease(
            $mutexKey,
            function() use ($payout, $ftaAccount){

                $this->trace->info(
                    TraceCode::FTA_MUTEX_ACQUIRED,
                    [
                        'payout_id'         => $payout->getId(),
                        'merchant_id'       => $payout->getMerchantId(),
                    ]);

                $this->createFundTransferAttemptProcess($payout, $ftaAccount);
            },
            self::FTA_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_FTA_ALREADY_BEING_PROCESSED);
    }

    public function fetchSplitzExperiment(string $merchantID, string $experimentID): bool
    {
        try {
            $properties = [
                "id" => $merchantID,
                "experiment_id" => $experimentID,
                'request_data'  => json_encode(['merchant_id' => $merchantID])
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variables = $response['response']['variant']['variables'];

            foreach ($variables as $variable) {
                if ($variable['key'] == "result" && $variable['value'] == "on") {
                    return true;
                }
            }
        } catch (\Throwable $e) {

            $this->trace->error(TraceCode::DOUBLE_FTA_SPLITZ_EXPERIMENT_FETCH_FAILED, [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return false;
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

    protected function getBankAccountToAssociateWithFTA(PublicEntity $bankAccount, Entity $payout)
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $payout->merchant;

        if (in_array(strtolower($merchant->getCountry()), BankAccount\Entity::$IfscAllowedCountries) === false)
        {
            return $bankAccount;
        }

        // We want to swap older IFSC to new IFSC for banks which are getting
        // merged to bigger banks. This is being done for now for IMPS payouts
        // only. This code will remain in FTA currently and will have to ported
        // to payouts as bank mergers will continue to happen.
        // Detailed discussion - https://razorpay.slack.com/archives/CM9230B5Y/p1606721863218100
        $ifscCode = $bankAccount->getIfscCode();
        $bankCode = $bankAccount->getAttribute(BankAccount\Entity::BANK_CODE) ?? '';

        if ($this->isIfscSwappingRequired($ifscCode) === true)
        {
            $ifscCode = $this->getNewIfscMapping($bankAccount->getIfscCode());
        }

        // Validate Whether IFSC is valid or not from rzp ifsc repo, if not get default IFSC for the bank
        $ifscCode = $this->validateIfscOrGetDefault($ifscCode, $merchant, $bankCode);

        if ($ifscCode === $bankAccount->getIfscCode())
        {
            return $bankAccount;
        }

        return $this->fetchOrCreateBankAccount($ifscCode, $bankAccount, $merchant);
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

    /**
     * @param string $ifscCode
     *
     * @return string Valid IFSC or Default IFSC for the bank
     * Function will check if IFSC is valid or not
     */
    private function validateIfscOrGetDefault(string $ifscCode,  Merchant\Entity $merchant, string $bankCode = '')
    {
        $ifscValidator = new BankAccount\Validator;

        try
        {
            $mode = $this->mode ?? Constants\Mode::LIVE;

            // Check if razorx enabled
            $razorxResponse = $this->app['razorx']->getTreatment($merchant->getId(),
                                                                 Merchant\RazorxTreatment::ALLOW_DEFAULT_IFSC_CODE,
                                                                 $mode);

            if ($razorxResponse !== 'on')
            {
                return $ifscCode;
            }

            $ifscValidator->validateIfscCode([BankAccount\Entity::IFSC_CODE => $ifscCode]);
        }
        catch (\Throwable $throwable)
        {
            if ($throwable->getMessage() === BankAccount\Validator::INVALID_IFSC_CODE_MESSAGE)
            {
                $defaultIfscCode = BankAccount\DefaultIfscMapping::getDefaultIfsc($ifscCode, $bankCode);

                if ($defaultIfscCode === BankAccount\DefaultIfscMapping::DEFAULT_IFSC_NOT_FOUND)
                {
                    return $ifscCode;
                }

                return $defaultIfscCode;
            }
        }

        return $ifscCode;
    }


    /**
     * @param string          $ifscCode
     * @param PublicEntity    $bankAccount
     * @param Merchant\Entity $merchant
     *
     * @return BankAccount\Entity
     */
    private function fetchOrCreateBankAccount(string $ifscCode,
                                              PublicEntity $bankAccount,
                                              Merchant\Entity $merchant): BankAccount\Entity
    {
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

        return $bankAccount;
    }
}
