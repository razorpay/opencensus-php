<?php

namespace RZP\Models\Reminders;

use Ramsey\Uuid\Uuid;
use RZP\Models\Ledger\Constants;
use RZP\Services\Ledger as LedgerService;
use RZP\Trace\TraceCode;

use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\CreditExpiry\Core as ReverseShadowCreditsExpiryCore;

class AmountCreditExpiryReminderProcessor extends ReminderProcessor
{
    CONST ACCOUNT_DEACTIVATE_REASON = 'Others';

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
            $creditsLog = $this->repo->credits->findOrFail($id);
            $accountId = $this->getAccountIdInLedgerIfExists($creditsLog->getMerchantId(), $id);
            if (empty($accountId) === false)
            {
                $this->deactivateAccountInLedger($accountId);
                return ['success' => true];
            }

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

    private function getAccountIdInLedgerIfExists($merchantId, $creditsId): ?string
    {
        $accountPayload = [
            Constants::MERCHANT_ID => $merchantId,
            Constants::ENTITIES => [
                // PG Merchant Amount Credit Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::REWARD_CREDITS]
                ],
            ],
        ];

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];

        $response = $this->app['ledger']->fetchAccountsByEntitiesAndMerchantID($accountPayload, $requestHeaders, true);

        $merchantAccountBalancesList = $response['body']['accounts'];

        foreach ($merchantAccountBalancesList as $account)
        {
            $entities = $account['entities'];
            if ((empty($entities['credit_id']) === false) && ($entities['credit_id'][0] === $creditsId))
            {
                return $account['id'];
            }
        }
        return null;
    }

    private function deactivateAccountInLedger($id)
    {
        $reqPayload = [
            Constants::ID => $id,
            Constants::REASON => self::ACCOUNT_DEACTIVATE_REASON
        ];
        $reqHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];
        $this->app['ledger']->deactivateAccount($reqPayload, $reqHeaders, true);
    }
}
