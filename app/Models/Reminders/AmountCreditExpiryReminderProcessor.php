<?php

namespace RZP\Models\Reminders;

use RZP\Trace\TraceCode;

use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\CreditExpiry\Core as ReverseShadowCreditsExpiryCore;

class AmountCreditExpiryReminderProcessor extends ReminderProcessor
{

    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $this->trace->info(TraceCode::PG_LEDGER_AMOUNT_CREDIT_EXPIRY_REMINDER_CALLBACK,
            [
                LedgerConstants::CREDIT_ID      => $id,
                LedgerConstants::DATA           => $data,
                LedgerConstants::ENTITY         => $entity,
                LedgerConstants::NAMESPACE      => $namespace,
            ]
        );

        try
        {
            $amountCreditsExpiryData = [
                LedgerConstants::CREDIT_ID => LedgerConstants::CREDITS_PREFIX.$id
            ];

            (new ReverseShadowCreditsExpiryCore())->createLedgerEntriesForAmountCreditsExpiry($amountCreditsExpiryData);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            $this->handleInvalidReminder();
        }

        return ['success' => true];
    }
}
