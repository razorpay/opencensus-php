<?php

namespace RZP\Modules\Acs;

use RZP\Exception\LogicException;

class RecordSyncListener
{
    protected $app;

    /** @var SyncEventManager $syncEventManager */
    protected $syncEventManager;

    public function __construct()
    {
        $this->app = \App::getFacadeRoot();
        $this->syncEventManager = $this->app[SyncEventManager::SINGLETON_NAME];
    }

    /**
     * @param RecordSyncEvent $event
     * @return void
     * @throws LogicException
     */
    public function handle(RecordSyncEvent $event)
    {
        $this->syncEventManager->recordAccountSync($event->entity, $event->outboxJobs);
    }
}
