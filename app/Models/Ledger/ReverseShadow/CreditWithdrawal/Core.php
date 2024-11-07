<?php

namespace RZP\Models\Ledger\ReverseShadow\CreditWithdrawal;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Merchant\Credits\Type;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Merchant\Balance;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{

    protected $merchant;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    /**
     * @param $creditsEntity
     * @return array
     */
    public function generateMoneyParamsForCreditWithdrawal($creditsEntity, $transactorEvent): array
    {
        $moneyParams = [];

        $amount = abs($creditsEntity->getValue());

        if ($transactorEvent == Constants::MERCHANT_REFUND_CREDIT_WITHDRAWAL) {
            $moneyParams[Constants::REFUND_CREDITS_AMOUNT] = strval($amount);
        }

        if ($transactorEvent == Constants::MERCHANT_FEE_CREDIT_WITHDRAWAL) {
            $moneyParams[Constants::FEE_CREDITS_AMOUNT] = strval($amount);
        }

        $moneyParams[Constants::AMOUNT]                       = strval($amount);
        $moneyParams[Constants::BASE_AMOUNT]                  = strval($amount);
        $moneyParams[Constants::CREDIT_CONTROL_AMOUNT]        = strval($amount);

        return $moneyParams;
    }

    /**
     * @param $creditsEntity
     * @return array
     */
    public function prepareJournalPayloadForCreditsWithdrawal($creditsEntity): array
    {
        $transactorEvent = "";
        if ($creditsEntity->getType() == Type::FEE_WITHDRAW) {
            $transactorEvent = Constants::MERCHANT_FEE_CREDIT_WITHDRAWAL;
        } else if ($creditsEntity->getType() == Type::REFUND_WITHDRAW) {
            $transactorEvent = Constants::MERCHANT_REFUND_CREDIT_WITHDRAWAL;
        }

        $transactionMessage = $this->generateBaseForJournalEntry($creditsEntity);

        $moneyParams = $this->generateMoneyParamsForCreditWithdrawal($creditsEntity, $transactorEvent);


        $transactionData = [
            Constants::TRANSACTOR_ID => $creditsEntity->getPublicId(),
            Constants::TRANSACTOR_EVENT => $transactorEvent,
            Constants::MONEY_PARAMS => $moneyParams,
            Constants::NOTES => [
                Constants::CREDIT_ID => $creditsEntity->getId()
            ]
        ];
        return array_merge($transactionMessage, $transactionData);
    }

    /**
     * @throws \Throwable
     * @throws LogicException
     */
    public function dispatchToSettlementFromJournalForCreditsWithdrawal($txn, $merchant): void
    {
        $bucketCore = new Bucket\Core;

        $preFundWithdrawBalance = new Balance\Entity();

        $preFundWithdrawBalance->setType(Balance\Type::PREFUND_WITHDRAWAL);

        $bucketCore->publishForSettlement($txn, $preFundWithdrawBalance);
    }

    public function transformJournalResponseToTransactionEntityForCreditsWithdrawal($journalResponse, $merchant): TransactionEntity
    {
        $baseTransactionEntity = $this->createTransactionEntityForPreFundWithdrawFromJournal($journalResponse, $merchant);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        $this->repo->transaction->saveOrFail($baseTransactionEntity);

        return $baseTransactionEntity;
    }
}
