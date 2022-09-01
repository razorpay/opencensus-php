<?php

namespace RZP\Models\Workflow\Service;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\WorkflowService;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Models\Workflow\Service\Adapter\Constants;

class Client
{
    const WFS_CONFIG_CREATE_ROUTE               = "twirp/rzp.workflows.config.v1.ConfigAPI/Create";
    const WFS_CONFIG_UPDATE_ROUTE               = "twirp/rzp.workflows.config.v1.ConfigAPI/Update";
    const WFS_CONFIG_GET_ROUTE                  = "twirp/rzp.workflows.config.v1.ConfigAPI/Get";
    const WFS_WORKFLOW_GET_ROUTE                = "twirp/rzp.workflows.workflow.v1.WorkflowAPI/Get";
    const WFS_WORKFLOW_LIST_BY_IDS_ROUTE        = "twirp/rzp.workflows.workflow.v1.WorkflowAPI/ListByIds";
    const WFS_ACTION_CREATE_ON_ENTITY_ROUTE     = "twirp/rzp.workflows.action.v1.ActionAPI/CreateWithEntityId";
    const WFS_DIRECT_ACTION_CREATE_ROUTE        = "twirp/rzp.workflows.action.v1.ActionAPI/CreateDirectOnWorkflow";
    const WFS_WORKFLOW_CREATE_ROUTE             = "twirp/rzp.workflows.workflow.v1.WorkflowAPI/Create";

    // self serve workflow routes
    const WORKFLOWS_CONFIG_CREATE_ROUTE         = "twirp/rzp.workflows.config.v1.ConfigAPI/CreateV2";
    const WORKFLOWS_CONFIG_UPDATE_ROUTE         = "twirp/rzp.workflows.config.v1.ConfigAPI/UpdateV2";
    const WORKFLOWS_CONFIG_DELETE_ROUTE         = "twirp/rzp.workflows.config.v1.ConfigAPI/DeleteV2";

    /** @var $workflowServiceClient WorkflowService */
    protected $workflowServiceClient;

    /** @var Trace */
    protected $trace;

    public function __construct()
    {
        $this->workflowServiceClient = app('workflow_service');

        $this->trace = app('trace');
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    public function createConfig(array $input)
    {
        $this->trace->info(TraceCode::WORKFLOW_SERVICE_TRACE_INFO, $input);

        $res = $this->workflowServiceClient->request(self::WFS_CONFIG_CREATE_ROUTE, $input);

        if ($res->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_CONFIG_CREATE_FAILED,
                $input);
        }

        // todo: save the config mapping in db

        return json_decode($res->body, true);
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\ServerErrorException|Exception\BadRequestException
     */
    public function createConfigV2(array $input): array
    {
        $this->trace->info(TraceCode::SELF_SERVE_WORKFLOW_SERVICE_TRACE_INFO, $input);

        $res = $this->workflowServiceClient->request(self::WORKFLOWS_CONFIG_CREATE_ROUTE, $input);

        if ($res->status_code !== 200)
        {
            $resBody = json_decode($res->body, true);

            $description = array_pull($resBody, 'msg', "Error occurred");

            if ($res->status_code >= 400 && $res->status_code <= 499)
            {
                $this->trace->error(TraceCode::BAD_REQUEST_WORKFLOW_CONFIG_CREATE_FAILED,
                    [
                        'response' => $res
                    ]);
                throw new Exception\BadRequestException(
                    $description,
                    ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_CREATE_FAILED
                );
            }
            else
            {
                $this->trace->error(TraceCode::SERVER_ERROR_WORKFLOW_CONFIG_CREATE_FAILED,
                    [
                        'response' => $res
                    ]);
                throw new Exception\ServerErrorException(
                    $description,
                    ErrorCode::SERVER_ERROR_WORKFLOW_CONFIG_CREATE_FAILED
                );
            }
        }

        return json_decode($res->body, true);
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\ServerErrorException|Exception\BadRequestException
     */
    public function updateConfigV2(array $input): array
    {
        $this->trace->info(TraceCode::SELF_SERVE_WORKFLOW_SERVICE_TRACE_INFO, $input);

        $res = $this->workflowServiceClient->request(self::WORKFLOWS_CONFIG_UPDATE_ROUTE, $input);

        if ($res->status_code !== 200)
        {
            $resBody = json_decode($res->body, true);

            $description = array_pull($resBody, 'msg', "Error occurred");

            if ($res->status_code >= 400 && $res->status_code <= 499)
            {
                $this->trace->error(TraceCode::BAD_REQUEST_WORKFLOW_CONFIG_UPDATE_FAILED,
                    [
                        'response' => $res
                    ]);
                throw new Exception\BadRequestException(
                    $description,
                    ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_UPDATE_FAILED
                );
            }
            else
            {
                $this->trace->error(TraceCode::SERVER_ERROR_WORKFLOW_CONFIG_UPDATE_FAILED,
                    [
                        'response' => $res
                    ]);
                throw new Exception\ServerErrorException(
                    $description,
                    ErrorCode::SERVER_ERROR_WORKFLOW_CONFIG_UPDATE_FAILED
                );
            }
        }

        return json_decode($res->body, true);
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestException
     */
    public function deleteConfig(array $input): array
    {
        $this->trace->info(TraceCode::SELF_SERVE_WORKFLOW_SERVICE_TRACE_INFO, $input);

        $res = $this->workflowServiceClient->request(self::WORKFLOWS_CONFIG_DELETE_ROUTE, $input);

        if ($res->status_code !== 200)
        {
            $resBody = json_decode($res->body, true);

            $description = array_pull($resBody, 'msg', "Error occurred");

            if ($res->status_code >= 400 && $res->status_code <= 499)
            {
                $this->trace->error(TraceCode::BAD_REQUEST_WORKFLOW_CONFIG_DELETE_FAILED,
                    [
                        'response' => $res
                    ]);
                throw new Exception\BadRequestException(
                    $description,
                    ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_DELETE_FAILED,
                    $input);
            }
            else
            {
                $this->trace->error(TraceCode::SERVER_ERROR_WORKFLOW_CONFIG_DELETE_FAILED,
                    [
                        'response' => $res
                    ]);
                throw new Exception\ServerErrorException(
                    $description,
                    ErrorCode::SERVER_ERROR_WORKFLOW_CONFIG_DELETE_FAILED,
                    $input);
            }
        }

        return json_decode($res->body, true);
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    public function updateConfig(array $input)
    {
        $this->trace->info(TraceCode::WORKFLOW_SERVICE_TRACE_INFO, $input);

        $res = $this->workflowServiceClient->request(self::WFS_CONFIG_UPDATE_ROUTE, $input);

        if ($res->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_CONFIG_UPDATE_FAILED,
                $input);
        }

        return json_decode($res->body, true);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    public function getConfigById(string $id)
    {
        $res = $this->workflowServiceClient->request(self::WFS_CONFIG_GET_ROUTE, ["id" => $id]);

        if ($res->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_CONFIG_GET_FAILED,
                ['id' => $id]);
        }

        return json_decode($res->body, true);
    }

    /**
     * @param Base\PublicEntity $entity
     * @param array $input
     * @return array
     */
    public function createWorkflow(Base\PublicEntity $entity, array $input = [])
    {
        $entityAdapter = $this->getEntityAdapter($entity);

        $payload = $entityAdapter->getWorkflowCreatePayload($entity, $input);

        $this->trace->info(TraceCode::WORKFLOW_SERVICE_TRACE_INFO, $payload);

        $res = $this->workflowServiceClient->request(self::WFS_WORKFLOW_CREATE_ROUTE, $payload);

        if ($res->status_code != 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_CREATE_FAILED,
                ['entity_id' => $entity->getPublicId(), 'input' => $input]);
        }

        $content = json_decode($res->body, true);

        return $entityAdapter->transformWorkflowResponse($content);
    }

    /**
     * @param string $id
     * @return array|mixed
     */
    public function getWorkflowById(string $id)
    {
        $payload['id'] = $id;

        $this->enrichWithActorFields($payload);

        $res = $this->workflowServiceClient->request(self::WFS_WORKFLOW_GET_ROUTE, $payload);

        if ($res->status_code != 200)
        {
            $this->trace->error(TraceCode::SERVER_ERROR_WORKFLOW_GET_FAILED, $payload);

            return [];
        }

        return json_decode($res->body, true);
    }

    /**
     * @param array $input
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    public function listWorkflowsByIds(array $input)
    {
        // todo: handle this properly later
        $payload = [];

        $this->enrichWithActorFields($payload);

        $res = $this->workflowServiceClient->request(self::WFS_WORKFLOW_LIST_BY_IDS_ROUTE, $input);

        if ($res->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_LIST_BY_IDS_FAILED,
                ['input' => $input]);
        }

        return json_decode($res->body, true);
    }

    /**
     * @param Base\PublicEntity $entity
     * @param array $input
     * @param array $optional_input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\ServerErrorException
     */
    public function createActionOnEntity(Base\PublicEntity $entity, array $input, array $optional_input = [])
    {
        $entityAdapter = $this->getEntityAdapter($entity);

        $actionPayload = $entityAdapter->getActionCreateOnEntityPayload($entity, $input);

        // this is applicable only for ICICI CA Flow
        // where payouts layer trigger workflow approve call for Owner state
        if (count($optional_input) > 0)
        {
            $input[Constants::ACTOR_ID] = $optional_input[Constants::ACTOR_ID];
            $input[Constants::ACTOR_TYPE] = $optional_input[Constants::ACTOR_TYPE];
            $input[Constants::ACTOR_PROPERTY_KEY] = $optional_input[Constants::ACTOR_PROPERTY_KEY];
            $input[Constants::ACTOR_PROPERTY_VALUE] = $optional_input[Constants::ACTOR_PROPERTY_VALUE];
            $input[Constants::SERVICE] = Constants::SERVICE_RX_LIVE;
            $input[Constants::ACTOR_META] =
                [
                    Constants::EMAIL => $optional_input[Constants::ACTOR_EMAIL],
                    Constants::NAME => $optional_input[Constants::ACTOR_NAME]
                ];
        }

        $this->trace->info(TraceCode::WORKFLOW_SERVICE_TRACE_INFO, $actionPayload);

        $res = $this->workflowServiceClient->request(self::WFS_ACTION_CREATE_ON_ENTITY_ROUTE, $actionPayload);

        $content = json_decode($res->body, true);

        if ($res->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_ACTION_CREATE_FAILED,
                ['entity_id' => $entity->getPublicId(), 'input' => $input]);
        }

        return $entityAdapter->transformActionResponse($content);
    }

    public function createDirectAction(Base\PublicEntity $entity, array $input)
    {
        $entityAdapter = $this->getEntityAdapter($entity);

        $actionPayload = $entityAdapter->getDirectActionCreatePayload($entity, $input);

        $this->trace->info(TraceCode::WORKFLOW_SERVICE_TRACE_INFO, $actionPayload);

        $res = $this->workflowServiceClient->request(self::WFS_DIRECT_ACTION_CREATE_ROUTE, $actionPayload);

        $content = json_decode($res->body, true);

        if ($res->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                null,
                ErrorCode::SERVER_ERROR_WORKFLOW_DIRECT_ACTION_CREATE_FAILED,
                ['entity_id' => $entity->getPublicId(), 'input' => $input]);
        }

        return $entityAdapter->transformDirectActionResponse($content);
    }

    protected function getEntityAdapter(Base\PublicEntity $entity): Adapter\Base
    {
        $type = $entity->getEntityName();

        $processor = 'RZP\Models\Workflow\Service\Adapter' . '\\' . studly_case($type);

        if (class_exists($processor) === false)
        {
            throw new Exception\RuntimeException('No adapter found for this entity', [
                'entity_name'                 => $type,
            ]);
        }

        return new $processor();
    }

    protected function enrichWithActorFields(array &$input)
    {
        $actorInfo = Adapter\Base::getActorInfo();

        $input['actor_id']              = $actorInfo['actor_id'];
        $input['actor_type']            = $actorInfo['actor_type'];
        $input['actor_property_key']    = $actorInfo['actor_property_key'];
        $input['actor_property_value']  = $actorInfo['actor_property_value'];
    }
}
