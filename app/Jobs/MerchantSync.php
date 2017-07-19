<?php

namespace RZP\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Merchant;
use RZP\Models\Admin\Group;
use RZP\Constants\Entity as E;

/**
 * Merchant index needs extended support for sync. Merchant index contains
 * groups and admins information for needed filtering support.
 *
 * This job listens for these changes and finds affected merchant ids which
 * need to be re-indexed and then pushes EsSync job to re-index those ids.
 */
class MerchantSync extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Group edit: When a group is edited with new parents we need
     * to find all merchants of hierarchical children of this group and re-index
     * those merchant entities.
     */
    const GROUP_EDIT   = 'group_edit';

    /**
     * Group delete: Same as above.
     */
    const GROUP_DELETE = 'group_delete';

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

        $handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $handler) === false)
        {
            $this->trace->critical(TraceCode::SERVER_ERROR_MISSING_HANDLER, $this->getTraceData());

            $this->delete();
        }

        $repo = $this->repoManager->merchant;

        $repo->setEsRepoIfExist();

        $this->esRepo = $repo->getEsRepo();

        try
        {
            $this->$handler();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, $this->getTraceData());

            $this->release();
        }
    }

    protected function handleGroupEdit()
    {
        $groupId = $this->payload[Group\Entity::ID];

        $params = [
            Merchant\Entity::GROUPS         => [$groupId],
            Merchant\Entity::ACCOUNT_STATUS => 'all',
        ];

        // Queries from ES itself for simplicity. We find all documents
        // which had this group id in their 'groups' attribute. These are the
        // merchant docs that needs to be re-indexed.

        $merchantIds = [];

        foreach ($this->esRepo->buildQuerySearchAndScroll($params) as $results)
        {
            $ids = array_collapse($results['hits']['hits'], '_id');

            $merchantIds = array_merge($merchantIds, $ids);
        }

        $this->pushEsSyncJob($merchantIds);
    }

    protected function handleGroupDelete()
    {
        $this->handleGroupEdit();
    }

    protected function pushEsSyncJob(array $merchantIds)
    {
        foreach ($merchantIds as $merchantId)
        {
            $job = new EsSync($this->mode, Merchant\EsRepository::UPDATE, E::MERCHANT, $id);

            (new DispatchRouter)->dispatchOn($job, DispatchRouter::ES_V2);
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
