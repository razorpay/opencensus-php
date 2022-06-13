<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Header;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;

class VaultMigrateTokenNs extends Base
{
    protected function processEntry(array & $entry)
    {
        try
        {
            $tokenInput = new PublicCollection;

            $entry[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE] = (int)$entry[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE];
            $entry[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE]       = (int)$entry[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE];

            $tokenInput->add($entry);

            $data = $this->app['card.cardVault']->migrateVaultTokenNamespace($tokenInput);

            $entry[Header::VAULT_MIGRATE_TOKEN_NAMESPACE_MIGRATED_TOKEN_ID] = $data['tokens'][0]['migrated_token_id'] ?? null;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::VAULT_MIGRATE_TOKEN_BULK_ERROR
            );

            $exceptionData = $e->getData();

            $entry[Header::ERROR_CODE] = $exceptionData['error'] ?? $e->getCode();
            $entry[Header::ERROR_DESCRIPTION] = $e->getMessage();
        }

    }
}
