<?php

namespace RZP\Models\Workflow\Service\Workflow;

use App;
use Route;
use Request;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Workflow\Service\Builder\Constants;
use \RZP\Models\Workflow\Action\Constants as ActionConstants;
use RZP\Models\Workflow\Service\Client;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;


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


    public function listPendingWorkflows($input) : array
    {
        /* @var $ba BasicAuth */
        $ba = app('basicauth');

        // Set merchant id if present (proxy auth)
        if ($ba->isProxyAuth())
        {
            $merchantId = $ba->getMerchantId();
            if (!empty($merchantId))
            {
                $input[Constants::WORKFLOW][Constants::OWNER_ID] = $merchantId;
            }
        }

        return $this->workflowServiceClient->listPendingWorkflows($input);
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

    /**
     * @param array $input
     * @throws BadRequestException
     */
    public function listCbWorkflows( array $input )
    {
        if (!isset($input[Constants::WORKFLOW][Constants::CONFIG_ID])) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CONFIG_ID);
        }
        $configId = $this->config->get('applications.workflows.cross_border.' . $input[Constants::WORKFLOW][Constants::CONFIG_ID]);
        if (!isset($configId)) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CONFIG_ID);
        }
        $input[Constants::WORKFLOW][Constants::CONFIG_ID] = $configId;
        $input[Constants::SELECTED_ENTITIES] = [Constants::STATES, Constants::ASSIGNEE];

        return $this->workflowServiceClient->listWorkflows($input);
    }

    public function createWorkflow(array $input): array
    {
        $this->app['trace']->info(TraceCode::CREATE_WORKFLOW_REQUEST, [
            'input' => $input,
        ]);

        // replacing the below implementation by findByOrgIdAndEmail, one email can be part of multiple org
        // for backward compatibility keeping RZP_ORG as default
        // $maker = $this->repo->admin->findByEmail($data[Constants::ADMIN_EMAIL]);
        $admin = $this->repo->admin->findByPublicId($input['admin_id']);

        if (array_key_exists($input['permission_name'],ActionConstants::PERMISSION_VS_CONTROLLER) == false){
            throw new Exception\BadRequestValidationFailureException("no approval controller defined");
        }

        $oldValue = array_get($input, 'input_old', null);
        $tagsValue = array_get($input, 'tags', ['AES_CREATED']);
        $newValue =  array_get($input, 'input', null);

        try
        {
            $this->app['workflow']
                ->setPermission($input['permission_name'])
                ->setRouteName($input['route_name'])
                ->setController(ActionConstants::PERMISSION_VS_CONTROLLER[$input['permission_name']])
                ->setMakerFromAuth(false)
                ->setInput($input['input'])
                ->setWorkflowMaker($admin)
                ->setWorkflowMakerType(MakerType::ADMIN)
                ->setEntityAndId(E::MERCHANT, $input['entity_id'])
                ->setTags($tagsValue)
                ->handle($oldValue, $newValue);
        }
        catch (Exception\EarlyWorkflowResponse $e)
        {
            throw $e;
        }

        return [];
    }

    public function getPermissionsOfAdmin(array $input, $adminId): array
    {
        $this->app['trace']->info(TraceCode::GET_ADMIN_PERMISSIONS_REQUEST, [
            'adminId'           => $adminId,
            'input'             => $input,

        ]);

        $admin = $this->repo->admin->findByPublicId($adminId);

        $permissions = [];


        foreach ($input['permissions'] as $permission){

            if ($admin->hasPermission($permission) === true)
            {
                $permissions[$permission] = true;
            }
            else
            {
                $permissions[$permission] = false;
            }
        }

        $this->app['trace']->info(TraceCode::GET_ADMIN_PERMISSIONS_RESPONSE, [
            'adminId'           => $adminId,
            'input'             => $permissions,

        ]);

        return ['permissions' => $permissions];
    }

}
