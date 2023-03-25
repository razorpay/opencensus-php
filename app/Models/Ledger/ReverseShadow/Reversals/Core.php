<?php
namespace RZP\Models\Ledger\ReverseShadow\Reversals;

use App;
use RZP\Exception;
use RZP\Models\Base;
use Ramsey\Uuid\Uuid;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Ledger\Constants;
use RZP\Models\Payment\Refund\Speed as Speed;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;

class Core extends Base\Core
{
    protected $merchant;

    protected $app;

    protected $repo;

    protected $trace;

    use ReverseShadowTrait;

    public function __construct()
    {
        parent::__construct();
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    /**
     * @throws Exception\LogicException
     * @throws \Throwable
     */
    public function createLedgerEntriesForReversalReverseShadow(ReversalEntity $reversal, RefundEntity $refund, Payment\Entity $payment, bool $feeOnlyReversal)
    {
        $transactorId = $reversal->getPublicId();

        $transactorEvent = Constants::REFUND_REVERSAL;

        list($moneyParams, $additionalParams) = $this->generateMoneyParamsAndRulesForRefundReversals($reversal, $refund, $feeOnlyReversal);

        $refundReversalData = array(
            Constants::TRANSACTOR_ID                 => $transactorId,
            Constants::TRANSACTOR_EVENT              => Constants::REFUND_REVERSAL,
            Constants::MONEY_PARAMS                  => $moneyParams,
            Constants::ADDITIONAL_PARAMS             => (count($additionalParams) > 0) ? $additionalParams : null,
            Constants::LEDGER_INTEGRATION_MODE       => Constants::REVERSE_SHADOW,
            Constants::IDEMPOTENCY_KEY               => Uuid::uuid1(),
            Constants::TENANT                        => Constants::TENANT_PG,
            Constants::IDENTIFIERS                   => [
                Constants::GATEWAY      => $reversal->entity->getGateway(),
            ],
        );

        $transactionMessage = $this->generateBaseForJournalEntry($refund);

        $journalPayload = array_merge($transactionMessage, $refundReversalData);

        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

       $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    private function generateMoneyParamsAndRulesForRefundReversals(ReversalEntity $reversal, RefundEntity $refund, bool $feeOnlyReversal): array
    {
        $moneyParams = [];
        $rule = [];
        $reversalAmount  = ($feeOnlyReversal === false) ? abs($reversal->getAmount()) : 0;
        $strAmount = strval($reversalAmount);
        $fees  = abs($reversal->getFee());
        $tax = abs($reversal->getTax());

        $commission = $fees - $tax;

        $ledgerService = $this->app['ledger'];

        // To check whether we need to reverse the amount to refund credits, we need to figure out whether the corresponding refund for the reversal
        // was triggered using refund credits or balance. To do this, we fetch the journal which has ledger_entry arr.
        // Each ledger entry has a fund account type based on which we identify if refund creds was used.
        $journal = $this->getJournalByTransactorInfo($refund->getPublicId(), Constants::REFUND_PROCESSED, $ledgerService);

        if ($journal ===  null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_JOURNAL_NOT_FOUND_ERROR,
                null,
                [
                    'reversal_id'=>$reversal->getPublicId(),
                    'refund_id'=>$refund->getPublicId(),
                ],
            );
        }
        $ledgerEntries = $journal[Constants::LEDGER_ENTRY];

        foreach ($ledgerEntries as $ledgerEntry)
        {
            if($ledgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_DEBIT)
            {
                $accountEntities = $ledgerEntry[Constants::ACCOUNT_ENTITIES];

                $fundAccountTypeArr = $accountEntities[Constants::FUND_ACCOUNT_TYPE];

                $fundAccountType = (count($fundAccountTypeArr) > 0) ? $fundAccountTypeArr[0] : "";

                // To ensure that refund forward transaction has this amount / fees debited, if debit is 0, it
                // could be a Direct Settlement just an authorized transaction refund - for which we have handled before this.

                if((intval($ledgerEntry[Constants::AMOUNT]) === 0) and ($fundAccountType === Constants::MERCHANT_BALANCE))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_REFUND_REVERSAL_NOT_APPLICABLE,
                        null,
                        [
                            'refund_id'=>$refund->getPublicId(),
                        ],
                    );
                }
                break;
            }
        }

        $isRefundCredits = $this->isRefundCreditsUsed($ledgerEntries);

        // all feeOnlyReversal cases occur in instant refunds where we have charged commission and tax.
        // We try to only reverse the fee and the reversed amount to merchant becomes only the fee and tax paid
        if($feeOnlyReversal === true)
        {
            $moneyParams[Constants::COMMISSION]         = strval($commission);
            $moneyParams[Constants::TAX]                = strval($tax);
            $moneyParams[Constants::REVERSED_AMOUNT]    = "0";

            if ($isRefundCredits === true)
            {
                $rule[Constants::REVERSE_REFUND_ACCOUNTING]     = Constants::INSTANT_REFUND_REVERSED_CREDITS;
                $moneyParams[Constants::REFUND_CREDITS]         = strval($commission + $tax);
            }
            else
            {
                $rule[Constants::REVERSE_REFUND_ACCOUNTING]         = Constants::INSTANT_REFUND_REVERSED;
                $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($commission + $tax);
            }
        }
        else
        {
            if($refund->getSpeedDecisioned() === speed::NORMAL)
            {
                if ($isRefundCredits === true)
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING] = Constants::REFUND_REVERSED_CREDITS;

                    $moneyParams[Constants::REVERSED_AMOUNT]     = $strAmount;
                    $moneyParams[Constants::REFUND_CREDITS]      = $strAmount;
                }
                else {
                    $moneyParams[Constants::REVERSED_AMOUNT]            = $strAmount;
                    $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]     = $strAmount;
                }
            }
            else if ($refund->isRefundSpeedInstant() === true)
            {
                $moneyParams[Constants::REVERSED_AMOUNT]    = $strAmount;
                $moneyParams[Constants::COMMISSION]         = strval($commission);
                $moneyParams[Constants::TAX]                = strval($tax);

                if ($isRefundCredits === true)
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING]     = Constants::INSTANT_REFUND_REVERSED_CREDITS;
                    $moneyParams[Constants::REFUND_CREDITS]         = strval($reversalAmount + $commission + $tax);
                }
                else
                {
                    $rule[Constants::REVERSE_REFUND_ACCOUNTING]         = Constants::INSTANT_REFUND_REVERSED;
                    $moneyParams[Constants::MERCHANT_BALANCE_AMOUNT]    = strval($reversalAmount + $commission + $tax);
                }
            }
        }

        return [$moneyParams, $rule];
    }

    private function isRefundCreditsUsed($ledgerEntries): bool
    {
        foreach ($ledgerEntries as $ledgerEntry)
        {
            $accountEntities = $ledgerEntry[Constants::ACCOUNT_ENTITIES];

            $fundAccountTypeArr = $accountEntities[Constants::FUND_ACCOUNT_TYPE];

            $fundAccountType = (count($fundAccountTypeArr) > 0) ? $fundAccountTypeArr[0] : "";

            if($fundAccountType === Constants::MERCHANT_REFUND_CREDITS)
            {
                return true;
            }
        }
        return false;
    }
}
