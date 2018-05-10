<?php

namespace RZP\Jobs;

use RZP\Constants\Es;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Group;
use RZP\Constants\Entity as E;
use RZP\Models\Base\EsRepository;

/**
 * Merchant index needs extended support for sync. Merchant index contains
 * groups and admins information for needed filtering support.
 *
 * This job listens for these changes and finds affected merchant ids which
 * need to be re-indexed and then pushes EsSync job to re-index those ids.
 */
class MerchantSync extends Job
{
    const MAX_JOB_ATTEMPTS = 3;
    const JOB_RELEASE_WAIT = 30;

    //
    // Handled events
    //
    const GROUP_EDIT       = 'group_edit';
    const GROUP_DELETE     = 'group_delete';

    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'es_sync';

    private $event;
    private $payload;
    private $esRepo;

    public function __construct(string $mode, string $event, array $payload)
    {
        parent::__construct($mode);

        $this->event   = $event;
        $this->payload = $payload;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::ES_SYNC_MERCHANT_REQUEST, $this->getTraceData());

        $handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $handler) === false)
        {
            $this->trace->critical(
                            TraceCode::SERVER_ERROR_MISSING_HANDLER,
                            $this->getTraceData());

            $this->delete();
        }

        $repo = $this->repoManager->merchant;

        $this->esRepo = $repo->setAndGetEsRepoIfExist();

        try
        {
            $this->$handler();

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::ES_SYNC_MERCHANT_FAILED,
                            $this->getTraceData());

            if($this->attempts() >= self::MAX_JOB_ATTEMPTS)
            {
                $this->delete();
            }
            else
            {
                $this->release(self::JOB_RELEASE_WAIT);
            }
        }
    }

    /**
     * Group edit: When a group is edited with new parents we need
     * to find all merchants of hierarchical children of this group and re-index
     * those merchant entities.
     *
     * Merchant ids to be re-indexed are queried from ES itself
     * for simplicity. We find all documents which had this group id
     * in their 'groups' attribute.
     */
    protected function handleGroupEdit()
    {
        $groupId = $this->payload[Group\Entity::ID];

        $params = [
            Merchant\Entity::GROUPS         => [$groupId],
            Merchant\Entity::ACCOUNT_STATUS => Merchant\AccountStatus::ALL,
        ];

        $merchantIds = [];

        foreach ($this->esRepo->buildQuerySearchAndScroll($params) as $results)
        {
            $ids = array_pluck($results[ES::HITS][ES::HITS], ES::_ID);

            $merchantIds = array_merge($merchantIds, $ids);
        }

        $this->pushEsSyncJob($merchantIds);
    }

    /**
     * Same as  handleGroupEdit()
     */
    protected function handleGroupDelete()
    {
        $this->handleGroupEdit();
    }

    /**
     * We already have flow (via EsSync) where particular entity gets re-indexed.
     * Using the same flow here.
     *
     * Cons:
     * - If there are thousands of merchant ids to be re-indexed this will push
     *   to queue that many times. In one way it's fine as those will be spread
     *   across listeners. But there is scope of optimization on db query when
     *   done in bulk.
     *
     * @param array $ids
     */
    protected function pushEsSyncJob(array $ids)
    {
        foreach ($ids as $id)
        {
            EsSync::dispatch($this->mode, EsRepository::UPDATE, E::MERCHANT, $id);
        }
    }

    protected function getTraceData()
    {
        return [
            'attempts' => $this->attempts(),
            'event'    => $this->event,
            'payload'  => $this->payload,
        ];
    }
}
