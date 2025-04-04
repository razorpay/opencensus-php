<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Admin\Org;
use RZP\Base\RepositoryManager;
use RZP\Models\Workflow\Action;
use RZP\Base\Repository as BaseRepository;
use RZP\Models\Workflow\Constants;
use RZP\Models\Workflow\Metric;

/**
 * Class Repository
 *
 * @package RZP\Models\Workflow\Base
 *
 * @property RepositoryManager $repo
 */
class Repository extends BaseRepository
{
    const ORG_ID = 'org_id';
    const ACTION_ID = 'action_id';


    public function getEntityName(): string {
        return "";
    }

    public function findByIdAndOrgId(string $id, string $orgId, array $relations = [])
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);
        $rows = $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::ID, '=', $id)
                    ->with($relations)
                    ->get();
        try {
            $entityId = $this->getEntityName() . '_id';
            $response = $this->app['WorkflowGuardService']->handleWorkflowGuardRequests($this->getEntityName(), 'findOrFail', [$entityId => $id], []);
            $entityNewColumns = Constants::WORKFLOW_GUARD_SERVICE_COLUMNS[$this->getEntityName()];
            $row = $rows->first();
            foreach($entityNewColumns as $column)
            {
                $row[$column] = $response[$this->getEntityName()][$column];
            }
        } catch (\Exception $e)
        {
            $this->trace->count(Metric::WFG_FIND_OR_FAIL_SUCCESS, ['entity' => $this->getEntityName(), 'findOrFail'=> __FUNCTION__]);
        }
        return $rows;
    }

    public function findByIdAndOrgIdWithRelations($id, $orgId, $relations = [])
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $dbResponse =  $this->newQuery()
                    ->orgId($orgId)
                    ->with($relations)
                    ->where(Entity::ID, '=', $id)
                    ->firstOrFailPublic();

        $this->appendWfgEntityData($dbResponse, $id);

        return $dbResponse;
    }

    public function findByPublicIdAndOrgIdWithRelations(
        $id,
        $orgId,
        $relations = [])
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->findByIdAndOrgIdWithRelations($id, $orgId, $relations);
    }

    public function saveOrFail($entity, array $options = array())
    {
        $dirty = $entity->getDirty();

        $input = [];

        $entityNewColumns = Constants::WORKFLOW_GUARD_SERVICE_COLUMNS[$entity->getEntity()] ?? [];

        foreach($entityNewColumns as $column)
        {
            if (isset($dirty[$column]))
            {
                $input[$column] = $dirty[$column];
                unset($entity[$column]);
            }
        }
        parent::saveOrFail($entity, $options);
        try
        {
            if (empty($input) === false)
            {
                $entityId = $this->getEntityName() . '_id';
                $input[$entityId] = $entity->getId();
                $request = [$this->getEntityName() => $input];
                $response = $this->app['WorkflowGuardService']->handleWorkflowGuardRequests($this->getEntityName(), __FUNCTION__, $request, []);
                $respEntity = $response[$this->getEntityName()] ?? [];
                $this->trace->count(Metric::WFG_SAVE_OR_FAIL_SUCCESS, ['entity' => $this->getEntityName()]);
                if(empty($respEntity) === false)
                {
                    // Update the entity with the new columns once the save is successful
                    foreach ($entityNewColumns as $column) {
                        $entity[$column] = $respEntity[$column];
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->count(Metric::WFG_SAVE_OR_FAIL_FAILED, ['entity' => $this->getEntityName()]);
        }
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $dbResponse = parent::findOrFail($id, $columns, $connectionType);
        $this->appendWfgEntityData($dbResponse, $id);
        return $dbResponse;
    }

    public function findOrFailPublic($id, $columns = ['*'], string $connectionType = null)
    {
        $dbResponse = parent::findOrFailPublic($id, $columns, $connectionType);
        $this->appendWfgEntityData($dbResponse, $id);
        return $dbResponse;
    }
    public function wfgFind(string $id): array
    {
        $entity = [];
        try
        {
            $entityId = $this->getEntityName() . '_id';
            $response = $this->app['WorkflowGuardService']->handleWorkflowGuardRequests($this->getEntityName(), 'findOrFail',[$entityId => $id], []);
            $entity = $response[$this->getEntityName()] ?? [];
            $this->trace->count(Metric::WFG_FIND_OR_FAIL_SUCCESS, ['entity' => $this->getEntityName()]);

        } catch (\Exception $e)
        {
            //Todo we are not returning error here, we should return error
            $this->trace->count(Metric::WFG_FIND_OR_FAIL_FAILED, ['entity' => $this->getEntityName()]);
        }
        return $entity;
    }

    protected function appendWfgEntityData(& $dbResponse, $id)
    {
        $wfgEntity = $this->wfgFind($id);
        $entityNewColumns = Constants::WORKFLOW_GUARD_SERVICE_COLUMNS[$this->getEntityName()] ?? [];
        if (empty($wfgEntity) === false)
        {
            foreach($entityNewColumns as $column)
            {
                $dbResponse[$column] = $wfgEntity[$column];
            }
        }
    }
}
