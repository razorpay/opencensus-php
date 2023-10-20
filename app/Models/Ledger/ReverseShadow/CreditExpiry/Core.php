<?php

namespace RZP\Models\Ledger\ReverseShadow\CreditExpiry;

use Ramsey\Uuid\Uuid;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;

class Core extends Base\Core
{
    use ReverseShadowTrait;

    public function createLedgerEntriesForAmountCreditsExpiry(array $amountCreditsExpiryJournalRequest)
    {
        $creditId = $amountCreditsExpiryJournalRequest[Constants::CREDIT_ID];

        $credit = $this->repo->credits->findByPublicId($creditId);

        $merchant = $this->repo->merchant->findOrFail($credit->getMerchantId());

        $transactorEvent = Constants::AMOUNT_CREDITS_EXPIRY_EVENT;

        $ledgerService = $this->app['ledger'];

        $journal = $this->getJournalByTransactorInfo($creditId, $transactorEvent, $ledgerService);

        $expiryAmount = $credit->getValue() - $credit->getUsed();

        if($journal == null)
        {
            // Create journal entries as the journal doesn't exist

            $journalPayload = [
                Constants::MERCHANT_ID                  => $credit->getMerchantId(),
                Constants::CURRENCY                     => $merchant->getCurrency(),
                Constants::TRANSACTION_DATE             => $credit->getExpiredAt(),
                Constants::TRANSACTOR_ID                => $creditId,
                Constants::TRANSACTOR_EVENT             => $transactorEvent,
                Constants::MONEY_PARAMS                 => [
                    Constants::EXPIRY_AMOUNT    => strval($expiryAmount)
                ],
                Constants::ADDITIONAL_PARAMS            => null,
                Constants::LEDGER_INTEGRATION_MODE      => Constants::REVERSE_SHADOW,
                Constants::IDEMPOTENCY_KEY              => Uuid::uuid1(),
                Constants::TENANT                       => Constants::TENANT_PG,
            ];

            $payloadName = $this->getPayloadName($creditId, $transactorEvent);

            $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

            $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
        }
    }
}
