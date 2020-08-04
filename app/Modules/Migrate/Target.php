<?php

namespace RZP\Modules\Migrate;

use Generator;

interface Target
{
    /**
     * Return array of opts on which iterate function can be invoked in parallel.
     *
     * @param array $opts
     * @return Generator
     */
    public function getParallelOpts(array $opts): Generator;

    /**
     * Yields records(of type Record) to be migrated.
     *
     * @param array $opts
     * @return Generator
     */
    public function iterate(array $opts): Generator;

    /**
     * This should implement migrating of a source record into target.
     *
     * E.g. an implementation will see if a record corresponding to $sourceRecord
     * exists, if not then should it create, if yes, then basis diff in records
     * should it update etc.
     *
     * @param Record  $sourceRecord
     * @param boolean $dryRun
     * @return Response
     */
    public function migrate(Record $sourceRecord, bool $dryRun): Response;

    /**
     * @param Record $record
     * @return void
     */
    public function delete(Record $record);
}
