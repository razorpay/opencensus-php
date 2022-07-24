<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use RZP\Models\Internal\Service;
use RZP\Trace\TraceCode;
use RZP\Models\Internal\Entity;

class Internal extends Base
{
    public function createLedgerPayloadFromEntity(Entity $internal, array $params = [])
    {
        $identifiers = $this->getFtsSourceAccountData($params);

        $payload = [
            Entity::MERCHANT_ID       => $internal[Entity::MERCHANT_ID],
            Entity::CURRENCY          => $internal[Entity::CURRENCY],
            Entity::AMOUNT            => strval($internal[Entity::AMOUNT]),
            Entity::BASE_AMOUNT       => strval($internal[Entity::BASE_AMOUNT]),
            self::TRANSACTOR_ID       => $internal->getPublicId(),
            self::TRANSACTOR_EVENT    => $params[Service::TRANSACTOR_EVENT],
            Entity::TRANSACTION_DATE  => strval($internal[Entity::TRANSACTION_DATE]),
            self::TENANT              => self::X,
            self::MODE                => $this->mode,
        ];

        if (empty($identifiers) === false)
        {
            $payload[self::IDENTIFIERS] = $identifiers;
        }


        $this->trace->info(
            TraceCode::LEDGER_REQUEST_PAYLOAD_CREATED,
            [
                'payload' => $payload,
            ]
        );

        return $payload;
    }

    protected function getFtsSourceAccountData(array $params = [])
    {

        // For Test Mode, we shall send default hardcoded data
        if ($this->mode === \RZP\Constants\Mode::TEST)
        {
            return [
                self::FTS_FUND_ACCOUNT_ID => self::DEFAULT_FTS_FUND_ACCOUNT_ID,
                self::FTS_ACCOUNT_TYPE    => self::DEFAULT_FTS_FUND_ACCOUNT_TYPE,
            ];
        }

        if (empty($params[Service::FTS_INFO]) === true)
        {
            return $params;
        }

        $params = $params[Service::FTS_INFO];

        return [
            self::FTS_FUND_ACCOUNT_ID => (string) $params[self::FTS_FUND_ACCOUNT_ID] ?? null,
            self::FTS_ACCOUNT_TYPE    => strtolower((string) $params[self::FTS_ACCOUNT_TYPE] )?? null,
        ];
    }
}
