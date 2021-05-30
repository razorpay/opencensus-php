<?php

namespace RZP\Models\Merchant\AccessMap;

use Generator;

use RZP\Modules\Migrate\Target;
use RZP\Modules\Migrate\Record;
use RZP\Modules\Migrate\Response;
use \RZP\Models\Merchant\MerchantApplications;

class MigrateKongTarget implements Target
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
        $core = new Core;
        $repo = app('repo');

        $repo->transaction(function () use ($core, $sourceRecord) {
            $core->createOutboxJob(
                "create_impersonation_grant",
                $sourceRecord->value,
                MerchantApplications\Entity::MANAGED);
        });

        return new Response(Response::ACTION_UPSERTED, $sourceRecord->key, null);
    }

    /** {@inheritDoc} */
    public function delete(Record $record)
    {
        // Not needed to implement.
    }
}
