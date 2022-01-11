<?php

namespace RZP\Models\Key;

use Generator;

use RZP\Modules\Migrate\Target;
use RZP\Modules\Migrate\Record;
use RZP\Modules\Migrate\Response;

class MigrateTarget implements Target
{
    /** {@inheritDoc} */
    public function getParallelOpts(array $opts): Generator
    {
        // TODO: To implement for recon.
        yield from [];
    }

    /** {@inheritDoc} */
    public function iterate(array $opts): Generator
    {
        // TODO: To implement for recon.
        yield from [];
    }

    /** {@inheritDoc} */
    public function migrate(Record $sourceRecord, bool $dryRun): Response
    {
        /** @var string */
        $mode = app('rzp.mode');

        // Credcase's migrate api internally does upsert.
        if ($dryRun === false)
        {
            app('repo')->transaction(function () use ($sourceRecord, $mode) {
                $credcase = new Credcase;
                $credcase->migrate($sourceRecord->value, $mode);
            });
        }

        // Credcase's migrate api does not return the record, assuming same and returning source record itself.
        return new Response(Response::ACTION_UPSERTED, $sourceRecord->key, $sourceRecord);
    }

    /** {@inheritDoc} */
    public function delete(Record $record)
    {
        // TODO: To implement for recon.
    }
}
