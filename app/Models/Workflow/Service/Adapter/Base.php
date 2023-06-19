<?php

namespace RZP\Models\Workflow\Service\Adapter;

use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Context;
use RZP\Trace\TraceCode;
use RZP\Http\OAuthScopes;
use RZP\Constants\Product;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Workflow\Service\EntityMap\Entity;

abstract class Base
{
    /** @var $ba BasicAuth */
    protected $ba;

    /** @var Trace */
    protected $trace;

    /** @var RepositoryManager */
    protected $repo;

    protected $request;

    public function __construct()
    {
        $this->repo     = app('repo');
        $this->trace    = app('trace');
        $this->ba       = app('basicauth');
        $this->request  = app('request');
    }

    /**
     * @param PublicEntity $entity
     * @param array $input
     * @return array
     */
    public function getActionCreateOnEntityPayload(PublicEntity $entity, array $input = []): array
    {
        $configId = $this->getWorkflowEntityMap($entity)->getConfigId();

        $isPayoutService = $entity->getIsPayoutService();
        $entityArr = $entity->toArray();

        $payload = [
            'config_id'     => $configId,
            'entity_id'     => $entityArr[PublicEntity::ID],
            'entity_type'   => $entity->getEntityName(),
            'action'        => $input['action'],
            'owner_id'      => $entity->getMerchantId(),
            'owner_type'    => Constants::MERCHANT,
            'comment'       => $input['user_comment'] ?? '',
            'data'          => $this->getCallBackDetails($entityArr, $input, $isPayoutService),
        ];

        $this->enrichActorFieldsForActions($payload);

        return $payload;
    }

    /**
     * @param PublicEntity $entity
     * @param array $input
     * @return array
     */
    public function getDirectActionCreatePayload(PublicEntity $entity, array $input = []): array
    {
        $workflowId = $this->fetchWorkflowIdForEntity($entity);

        $payload = [
            'workflow_id'   => $workflowId,
            'action_type'   => $input['action'],
            'comment'       => $input['user_comment'] ?? '',
            'owner_id'      => $entity->getMerchantId(),
            'owner_type'    => Constants::MERCHANT
        ];

        if (empty($input['data']) === false)
        {
            $payload += ['data' => $input['data']];
        }

        $this->enrichActorFieldsForDirectActions($payload, $input);

        return ['action' => $payload];
    }

    /**
     * @param PublicEntity $entity
     * @param array $input
     * @return array
     */
    public function getWorkflowCreatePayload(PublicEntity $entity, array $input = [])
    {
        $configId = $this->fetchConfigIdForEntity($entity, $input);

        $isPayoutService = $entity->getIsPayoutService();
        $entityArr = $entity->toArray();

        $payload = [
            'entity_id'         => $entityArr[PublicEntity::ID],
            'entity_type'       => $entity->getEntityName(),
            'title'             => $input['title'] ?? '',
            'description'       => $input['description'] ?? '',
            'callback_details'  => $this->getCallBackDetails($entityArr, $input, $isPayoutService),
            'config_id'         => $configId,
            'config_version'    => '1',
            'diff'              => $this->getDiffForWorkflow($entityArr),
        ];

        $this->enrichFieldsForWorkflows($payload, $entity);

        return ['workflow' => $payload];
    }

    abstract public function getCallBackDetails(array $entityArr, array $input, bool $isPayoutService);

    abstract public function getDiffForWorkflow(array $entityArr);

    public function transformActionResponse(array $content): array
    {
        if (empty($content) ||
            !isset($content['items']) ||
            !isset($content['items'][0]))
        {
            $this->trace->error(TraceCode::SERVER_ERROR_WORKFLOW_SERVICE_RESPONSE_MALFORMED, $content);

            throw new \Exception('Malformed response received');
        }

        $action = $content['items'][0];

        return $this->getActionResponse($action);
    }

    public function transformDirectActionResponse(array $content): array
    {
        if (empty($content) === true)
        {
            $this->trace->error(TraceCode::SERVER_ERROR_WORKFLOW_SERVICE_RESPONSE_MALFORMED, $content);

            throw new \Exception('Malformed response received');
        }

        return $this->getActionResponse($content);
    }

    private function getActionResponse(array $action)
    {
        return [
            Constants::ID                      => $action[Constants::ID] ?? null,
            Constants::WORKFLOW_ID             => $action[Constants::WORKFLOW_ID] ?? null,
            Constants::STATE_ID                => $action[Constants::STATE_ID] ?? null,
            Constants::ACTION_TYPE             => $action[Constants::ACTION_TYPE] ?? null,
            Constants::COMMENT                 => $action[Constants::COMMENT] ?? null,
            Constants::STATUS                  => $action[Constants::STATUS] ?? null,
            Constants::ACTOR_ID                => $action[Constants::ACTOR_ID] ?? null,
            Constants::ACTOR_TYPE              => $action[Constants::ACTOR_TYPE] ?? null,
            Constants::ACTOR_PROPERTY_KEY      => $action[Constants::ACTOR_PROPERTY_KEY] ?? null,
            Constants::ACTOR_PROPERTY_VALUE    => $action[Constants::ACTOR_PROPERTY_VALUE] ?? null,
        ];
    }

    public function transformWorkflowResponse(array $content): array
    {
        return [
            Constants::WORKFLOW_ID     => $content[Constants::ID],
            Constants::CONFIG_ID       => $content[Constants::CONFIG_ID],
            Constants::STATUS          => $content[Constants::STATUS],
            Constants::DOMAIN_STATUS   => $content[Constants::DOMAIN_STATUS],
        ];
    }

    /**
     * @param array $payload
     * @param PublicEntity $entity
     */
    protected function enrichFieldsForWorkflows(array &$payload, PublicEntity $entity)
    {
        $user = $this->ba->getUser();
        $merchant = $entity->merchant;

        $payload += [
            'creator_id'        => ($user !== null) ? $user->getId() : $entity->getMerchantId(),
            'creator_type'      => ($user !== null) ? Constants::USER : Constants::MERCHANT,
            'owner_id'          => $entity->getMerchantId(),
            'owner_type'        => Constants::MERCHANT,
            'service'           => Constants::SERVICE_RX . $this->ba->getMode(),
            'org_id'            => $merchant->getOrgId(),
        ];
    }

    /**
     * @param array $input
     */
    protected function enrichActorFieldsForActions(array &$input)
    {
        $this->enrichActorAndServiceDetails($input);

        // todo: handle actor meta in case of admins
        $user = $this->ba->getUser();
        $actorName                       = ($user !== null) ? $user->getName() : '';
        $actorEmail                      = ($user !== null) ? $user->getEmail() : '';

        $input['actor_meta']            = ['email' => $actorEmail, 'name' => $actorName];
    }

    /**
     * @param array $payload
     * @param array $input
     */
    protected function enrichActorFieldsForDirectActions(array &$payload, array $input)
    {
        $this->enrichActorAndServiceDetails($payload);

        if (array_key_exists(PayoutEntity::BULK_REJECT_AS_OWNER, $input))
        {
            $ba = app('basicauth');

            $owner = $ba->getUser();

            $payload[Constants::ACTOR_ID]               = $owner->getId();

            $payload[Constants::ACTOR_TYPE]             = Constants::OWNER;

            $payload[Constants::ACTOR_PROPERTY_KEY]     = Constants::ROLE;

            $payload[Constants::ACTOR_PROPERTY_VALUE]   = Constants::OWNER;

            $payload[Constants::SERVICE]                = Constants::SERVICE_RX . $this->ba->getMode();

            $payload[Constants::ACTOR_META]             = [
                                                                Constants::EMAIL => $owner->getEmail(),
                                                                Constants::NAME => $owner->getName()
                                                            ];
        }
    }

    private function enrichActorAndServiceDetails(array &$input)
    {
        $actorInfo = self::getActorInfo();

        $input['actor_id']              = $actorInfo['actor_id'];
        $input['actor_type']            = $actorInfo['actor_type'];
        $input['actor_property_key']    = $actorInfo['actor_property_key'];
        $input['actor_property_value']  = $actorInfo['actor_property_value'];

        // todo: make this generic
        $input['service'] = Constants::SERVICE_RX . $this->ba->getMode();
    }

    /**
     * Workflow system takes the config from the
     * workflow_entity_map table. This let's the merchant update the config
     * without affecting the existing workflows
     *
     * @param PublicEntity $entity
     * @param array $input
     * @return mixed
     */
    protected function fetchConfigIdForEntity(PublicEntity $entity, array $input)
    {
        $workflowType = $input[Constants::WORKFLOW_TYPE];
        $merchantId = $entity->getMerchantId();

        $config = $this->repo->workflow_config->getByConfigTypeAndMerchantId($workflowType, $merchantId);

        return $config->getConfigId();
    }

    /**
     * The approval/rejection workflow takes the config from the
     * workflow_entity_map table. This let's the merchant update the config
     * without affecting the existing entities.
     *
     * @param PublicEntity $entity
     * @return mixed
     */
    protected function fetchWorkflowIdForEntity(PublicEntity $entity)
    {
        $workflowEntity = $this->getWorkflowEntityMap($entity);

        return $workflowEntity->getWorkflowId();
    }

    /**
     * @param PublicEntity $entity
     * @return Entity
     */
    private function getWorkflowEntityMap(PublicEntity $entity)
    {
        /* @var $workflowEntity Entity  */
        $workflowEntity = $this->repo->workflow_entity_map->findByEntityIdAndEntityType(
            $entity->getEntityName(), $entity->getId());

        if (empty($workflowEntity) === true)
        {
            throw new \Exception('workflow was not processed via workflow service');
        }

        return $workflowEntity;
    }

    /**
     * This function should not be a part of this class.
     * Ideally this should be at a central place that governs whether a token has acess to a resource
     * Since no new changes are being accepted in BasicAuth, adding it as a function here. Needs to be refactored.
     */
    public static function isXPartnerApproval()
    {
        /** @var $auth BasicAuth */
        $auth = app('basicauth');

        if ($auth->getAccessTokenId() === null)
        {
            return false;
        }

        $scopes = $auth->getTokenScopes();

        if (empty($scopes) === true or (in_array(OAuthScopes::RX_PARTNER_READ_WRITE, $scopes, true) === false))
        {
            return false;
        }

        return $auth->getMerchant()->isFeatureEnabled(Feature::ENABLE_APPROVAL_VIA_OAUTH) === true;
    }

    /**
     * Returns 'internal'/service for cron auth,
     * Returns admin id/admin for admin auth
     * Returns user id or merchant id/user for proxy/private auth types
     *
     * @return mixed|string
     */
    public static function getActorInfo()
    {
        /** @var $ba BasicAuth */
        $ba = app('basicauth');

        /** @var $repo RepositoryManager */
        $repo = app('repo');

        /** @var $workerCtx Context */
        $workerCtx = app('worker.ctx');

        $actorPropertyKey = Constants::ROLE;

        $user       = $ba->getUser();
        $admin      = $ba->getAdmin();
        $merchant   = $ba->getMerchant();

        if ($ba->isAdminAuth() === true)
        {
            $actorId = $admin->getId();
            $actorType = Constants::ADMIN;
            $actorPropertyValue = Constants::ADMIN;
        }
        else if (empty($workerCtx->getJobName()) === false)
        {
            // this is used for queue worker use cases like (scheduled payouts)
            $actorId = Constants::INTERNAL_ACTOR_NAME;
            $actorType = Constants::SERVICE;
            $actorPropertyKey = Constants::NAME;
            $actorPropertyValue = Constants::SERVICE_RX . $ba->getMode(); // todo: make this generic
        }
        else if ($ba->isStrictPrivateAuth() === true) {
            $actorId = $merchant->getId();
            $actorType = Constants::MERCHANT;
            $actorPropertyValue = Constants::API;

            if ($ba->isSlackApp() === true || $ba->isAppleWatchApp() || BASE::isXPartnerApproval()) {
                $actorType = Constants::USER;
                $actorId = $user->getId();
                $actorPropertyValue = ($repo->merchant->getMerchantUserMapping($merchant->getId(), $user->getId(), null, Product::BANKING))->pivot->role;
            }
        }
        else
        {
            if (empty($user) === false)
            {
                $actorId = $user->getId();
                $actorType = Constants::USER;
                $userMapping = $repo->merchant->getMerchantUserMapping(
                    $merchant->getId(), $user->getId(), null, Product::BANKING); // todo: make this generic
                $actorPropertyValue = $userMapping->pivot->role;
            }
            elseif (empty($merchant) === false)
            {
                $actorId = $merchant->getId();
                $actorType = Constants::MERCHANT;
                $actorPropertyValue = $ba->getInternalApp();
            }
            else
            {
                // this happens when the wfs sends a reject callback and payout->toArray() is invoked in the
                // payout.rejected webhook trigger flow, on app auth without merchant context
                $actorId = '';
                $actorType = '';
                $actorPropertyValue = '';
            }
        }

        return [
            'actor_id'              => $actorId,
            'actor_type'            => $actorType,
            'actor_property_key'    => $actorPropertyKey,
            'actor_property_value'  => $actorPropertyValue,
        ];
    }
}
