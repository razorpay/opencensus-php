<?php

namespace RZP\Models\Ledger\ReverseShadow\ReserveBalanceWithdrawal;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Adjustment\Entity as AdjustmentEntity;
use RZP\Models\Base;
use RZP\Models\Ledger\BaseJournalEvents as BaseJournalEvents;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Transaction;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Merchant\Balance\Type as MerchantBalanceType;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Merchant\Balance;


class Core extends Base\Core
{
    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }
    public function generateMoneyParamsForReserveBalanceWithdrawal($adjustment): array
    {
        $moneyParams = [];

        $amount =  abs($adjustment->getAmount());

        $moneyParams[Constants::AMOUNT] = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT] = strval($amount);
        $moneyParams[Constants::RESERVE_BALANCE_AMOUNT] = strval($amount);
        $moneyParams[Constants::RESERVE_BALANCE_CONTROL_AMOUNT] = strval($amount);

        return $moneyParams;
    }

    public function prepareJournalPayloadForReserveBalanceWithdrawal($adjustment): array
    {
        $transactionMessage = $this->generateBaseForJournalEntry($adjustment);

        $moneyParams = $this->generateMoneyParamsForReserveBalanceWithdrawal($adjustment);

        $transactionData = [
            Constants::TRANSACTOR_ID => $adjustment->getPublicId(),
            Constants::TRANSACTOR_EVENT => Constants::MERCHANT_RESERVE_BALANCE_WITHDRAWAL,
            Constants::MONEY_PARAMS => $moneyParams,
            Constants::NOTES => [
                Constants::RESERVE_BALANCE_ID => $adjustment->getId()
            ]
        ];
        return array_merge($transactionMessage, $transactionData);

    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function createReserveBalanceWithdrawalLedgerAndPostToSettlement($adjustment, $merchant): void
    {
        $journalPayload = $this->prepareJournalPayloadForReserveBalanceWithdrawal($adjustment);
        $journal = $this->createJournalInLedger($journalPayload);

        $this->createTxnAndDispatchToSettlementFromJournalForReserveBalance($journal, $merchant, $adjustment);
    }


    /**
     * @throws \Throwable
     * @throws LogicException
     */
    public function createTxnAndDispatchToSettlementFromJournalForReserveBalance($journal, $merchant, $adjustment): void
    {
        $reserveBalanceWithdrawalTransaction = $this->transformJournalResponseToTransactionEntityForAdjustment($journal);

        $bucketCore = new Bucket\Core;

        $preFundWithdrawBalance = new Balance\Entity();

        $preFundWithdrawBalance->setType(Balance\Type::PREFUND_WITHDRAWAL);

        $bucketCore->publishForSettlement($reserveBalanceWithdrawalTransaction, $preFundWithdrawBalance);
    }
}
