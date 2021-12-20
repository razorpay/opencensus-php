<?php


namespace RZP\Models\Transaction\Statement\Ledger\Statement;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Transaction\Statement;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement\Ledger\Statement
 */
class Repository extends Base\Repository
{
    protected $entity = 'ledger_statement';

    /**
     * In GET and LIST for only source of type payout laze loads following nested relations.
     * This array is used when a transaction is fetched via ledger flow.
     * @var array
     */
    protected $expandsForTypePayoutForLedger = [
        'fundAccount.contact',
        'fundAccount.account',
        'reversal',
    ];

    /**
     * In GET and LIST for only source of type fund account validation laze loads following nested relations.
     * This array is used when a transaction is fetched via ledger flow.
     * @var array
     */
    protected $expandsForTypeFAVForLedger = [
        'fundAccount.contact',
        'fundAccount.account',
    ];

    /**
     * Set source field on transaction array depending on the source type.
     * @param string $sourceId
     * @param string $sourceType
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    public function setSourceForTransaction(string $sourceId, string $sourceType, array &$transaction, Merchant\Entity $merchant) {
        switch ($sourceType)
        {
            case E::PAYOUT:
                $this->setPayoutAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::BANK_TRANSFER:
                $this->setBankTransferAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::ADJUSTMENT:
                $this->setAdjustmentAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::REVERSAL:
                $this->setReversalAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::FUND_ACCOUNT_VALIDATION:
                $this->setFundAccountValidationAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            default:
                throw new LogicException(SERVICE::SOURCE . ' not implemented at ledger : ' . $sourceType);
        }
    }

    /**
     * Fetch payout entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setPayoutAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant) {
        $payout = $this->repo->payout->fetchPayoutWithExpands(PublicEntity::stripDefaultSign($id), $this->expandsForTypePayoutForLedger);

        // Adding fund_account in extraSourceFields because when doing $payout->toArrayPublic(), "fund_account"
        // key gets removed since it is not present in $visible array. Merging it in $source array later.
        $extraSourceFields = [
            Payout\Entity::FUND_ACCOUNT => $payout->fundAccount->toArrayPublic(),
        ];

        $transaction[Service::SOURCE] = $payout->toArrayPublic();
        $transaction[Service::SOURCE] = array_merge($transaction[Service::SOURCE], $extraSourceFields);

        // Calling statement entity function to set public attributes for payout entity.
        $statement = new Statement\Entity();
        $statement->setPublicSourceAttributeForPayout($transaction);
    }

    /**
     * Fetch bank_transfer entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setBankTransferAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant) {
        $bankTransfer = $this->repo->bank_transfer->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $bankTransfer->toArrayPublic();

        // Calling statement entity function to set public attributes for bank_transfer entity.
        $statement = new Statement\Entity();

        // Initializing source since inside setPublicSourceAttributeForBankTransfer, bank_transfer us fetched from source
        $statement->setRelation('source', $bankTransfer);
        $statement->setPublicSourceAttributeForBankTransfer($transaction);
    }

    /**
     * Fetch external entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setAdjustmentAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant) {
        $adjustment = $this->repo->adjustment->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $adjustment->toArrayPublic();

        // Calling statement entity function to set public attributes for adjustment entity.
        $statement = new Statement\Entity();
        $statement->setPublicSourceAttributeForAdjustment($transaction);
    }

    /**
     * Fetch reversal entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setReversalAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant) {
        $reversal = $this->repo->reversal->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $reversal->toArrayPublic();

        // Since currently no specific function is present in statement entity to return
        // any other fields for reversal entity, that's why returning directly.
    }

    /**
     * Fetch fund_account_validation entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setFundAccountValidationAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant) {
        $fav = $this->repo->fund_account_validation->fetchFAVWithExpands(PublicEntity::stripDefaultSign($id), $this->expandsForTypeFAVForLedger);

        // Adding fund_account in extraSourceFields because when doing $fav->toArrayPublic(), "fund_account"
        // key gets removed since it is not present in $visible array. Merging it in $source array later.
        $extraSourceFields = [
            Payout\Entity::FUND_ACCOUNT => $fav->fundAccount->toArrayPublic(),
        ];

        $transaction[Service::SOURCE] = $fav->toArrayPublic();
        $transaction[Service::SOURCE] = array_merge($transaction[Service::SOURCE], $extraSourceFields);

        // Since currently no specific function is present in statement entity to return
        // any other fields for fund_account_validation entity, that's why returning directly.
    }
}
