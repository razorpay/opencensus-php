<?php

namespace RZP\Models\Merchant\AccessMap;

use Generator;

use RZP\Modules\Migrate\Target;
use RZP\Modules\Migrate\Record;
use RZP\Modules\Migrate\Response;
use \RZP\Models\Merchant\MerchantApplications;

class MigrateKongTarget implements Target
{

    /**
     * @var $enable_cassandra_outbox
     */
    protected $enable_cassandra_outbox;

    /**
     * @var $enable_postgres_outbox
     */
    protected $enable_postgres_outbox;

    public function __construct() {
        $this->enable_cassandra_outbox = env("ENABLE_CASSANDRA_OUTBOX", true);

        $this->enable_postgres_outbox = env("ENABLE_POSTGRES_OUTBOX", false);
    }

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
            $this->outboxSend($core, $sourceRecord, 'create');
        });

        return new Response(Response::ACTION_UPSERTED, $sourceRecord->key, null);
    }

    /** {@inheritDoc} */
    public function delete(Record $record)
    {
        $core = new Core;
        $repo = app('repo');

        $repo->transaction(function () use ($core, $record) {
            $this->outboxSend($core, $record, 'delete');
        });

        return null;
    }

    /**
     * @param Core $core
     * @param Record $sourceRecord
     * @return void
     */
    function outboxSend(Core $core, Record $sourceRecord, string $action): void {
        if ($this->enable_cassandra_outbox)
        {
            $core->createOutboxJob(
                $action . "_impersonation_grant",
                $sourceRecord->value,
                MerchantApplications\Entity::MANAGED);
        }
        if ($this->enable_postgres_outbox)
        {
            $core->createOutboxJob(
                $action . " _impersonation_grant_postgres",
                $sourceRecord->value,
                MerchantApplications\Entity::MANAGED);
        }
    }
}
