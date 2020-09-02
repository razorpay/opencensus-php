<?php

namespace RZP\Models\Merchant\AccessMap;

use Generator;

use RZP\Modules\Migrate\Target;
use RZP\Modules\Migrate\Record;
use RZP\Modules\Migrate\Response;
use RZP\Models\Merchant\WebhookV2\Stork;

class MigrateStorkTarget implements Target
{
    /** {@inheritDoc} */
    public function getParallelOpts(array $opts): Generator
    {
        // Not needed to implement.
        yield from [];
    }

    /** {@inheritDoc} */
    public function iterate(array $opts): Generator
    {
        // Not needed to implement.
        yield from [];
    }

    /** {@inheritDoc} */
    public function migrate(Record $sourceRecord, bool $dryRun): Response
    {
        /** @var Entity */
        $accessMap = $sourceRecord->value;

        // Dry run flag is not honored in this case because not needed :)
        (new Stork($accessMap->getConnectionName()))->invalidateAffectedOwnersCache($accessMap->getMerchantId());

        return new Response(Response::ACTION_UPSERTED, $sourceRecord->key, null);
    }

    /** {@inheritDoc} */
    public function delete(Record $record)
    {
        // Not needed to implement.
    }
}
