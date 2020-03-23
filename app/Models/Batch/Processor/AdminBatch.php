<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Admin as AdminModel;

class AdminBatch extends Base
{
    /**
     * @var AdminModel\Core
     */
    protected $adminCore;

    /**
     * AdminBatch constructor.
     *
     * @param Batch\Entity $batch
     */
    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->adminCore = new AdminModel\Core;

    }

    /**
     * Batch ProcessEntry Default Function to process each entry
     *
     * @param array $entry
     */
    protected function processEntry(array & $entry)
    {
        $adminEntity = $this->repo->admin->findByPublicId($entry[Batch\Header::ADMIN_ID]);

        $this->processEntryForAdminEntity($entry, $adminEntity);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    /**
     *
     * @param array             $entry
     * @param AdminModel\Entity $adminEntity
     *
     * @return AdminModel\Entity
     */
    protected function processEntryForAdminEntity(array & $entry, AdminModel\Entity $adminEntity): AdminModel\Entity
    {
        $input = Batch\Helpers\AdminEntityInputFilter::getAdminInput($entry, $adminEntity);

        $this->trace->info(
            TraceCode::ADMIN_BATCH_UPDATE,
            [
                'Input data verified',
                'batch_entry' => $entry
            ]);

        $updatedAdmin = (new AdminModel\Service)->validateAndEditAdmin("admin_" . $adminEntity->getId(), $input);

        return $updatedAdmin;
    }
}
