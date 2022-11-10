<?php

namespace RZP\Models\Workflow\Service\Workflow;

use RZP\Models\Base;
use RZP\Exception\BadRequestException;
use RZP\Models\Workflow\Service\Builder\Constants;
use RZP\Models\Workflow\Service\Client;

class Service extends Base\Service
{

    protected $workflowServiceClient;

    protected $config;

    public function __construct()
    {
        parent::__construct();

        $this->workflowServiceClient = new Client;
        $this->config   = app('config');
    }


    /**
     * @param array $input
     * @throws BadRequestException
     */
    public function listWorkflows( array $input )
    {
        $input[Constants::WORKFLOW][Constants::CONFIG_ID] = $this->config->get('applications.workflows.spr_config_id');
        $input[Constants::SELECTED_ENTITIES] = [Constants::STATES, Constants::ASSIGNEE];

        return $this->workflowServiceClient->listWorkflows($input);
    }

    public function getWorkflow( string $id )
    {
        return $this->workflowServiceClient->getWorkflowById($id);
    }

    public function createWorkflowAction( array $input )
    {
        return $this->workflowServiceClient->createActionOnEntityProxy( $input);
    }

    public function addWorkflowAssignee( array $input )
    {
        return $this->workflowServiceClient->addWorkflowAssignee($input);
    }

    public function removeWorkflowAssignee( array $input )
    {
        return $this->workflowServiceClient->removeWorkflowAssignee($input);
    }

    public function createComment( array $input )
    {
        return $this->workflowServiceClient->createComment($input);
    }

    public function listComments( array $input )
    {
        return $this->workflowServiceClient->listComments($input);
    }
}
