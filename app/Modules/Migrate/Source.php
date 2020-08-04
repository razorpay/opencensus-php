<?php

namespace RZP\Modules\Migrate;

use Generator;

interface Source
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
     * @param Record $targetRecord
     * @return Record|null
     */
    public function find(Record $targetRecord);
}
