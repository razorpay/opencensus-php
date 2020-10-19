<?php

namespace RZP\Models\Workflow\Service\Adapter;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Workflow\Service\EntityMap\Entity;

abstract class Base
{
    /** @var $ba BasicAuth */
    protected $ba;

    /** @var Trace */
    protected $trace;

    /** @var RepositoryManager */
    protected $repo;

    public function __construct()
    {
        $this->repo     = app('repo');
        $this->trace    = app('trace');
        $this->ba       = app('basicauth');
    }

    /**
     * @param PublicEntity $entity
     * @param array $input
     * @return array
     */
    public function getActionCreateOnEntityPayload(PublicEntity $entity, array $input = []): array
    {
        $configId = $this->fetchWorkflowConfigIdForEntity($entity);

        $entityArr = $entity->toArray();

        $payload = [
            'config_id'     => $configId,
            "entity_id"     => $entityArr[PublicEntity::ID],
            "entity_type"   => $entity->getEntityName(),
            "action"        => $input['action'],
            'owner_id'      => $entity->getMerchantId(),
            'owner_type'    => Constants::MERCHANT,
            "comment"       => $input['user_comment'] ?? "",
            "data"          => $this->getCallBackDetails($entityArr, $input),
        ];

        $this->enrichUserFieldsForActions($payload);

        return $payload;
    }

    /**
     * @param PublicEntity $entity
     * @param array $input
     * @return array
     */
    public function getWorkflowCreatePayload(PublicEntity $entity, array $input = [])
    {
        $configId = $this->fetchWorkflowConfigIdForEntity($entity);

        $entityArr = $entity->toArray();

        $payload = [
            "entity_id"         => $entityArr[PublicEntity::ID],
            "entity_type"       => $entity->getEntityName(),
            "title"             => $input['title'] ?? "",
            "description"       => $input['description'] ?? "",
            "callback_details"  => $this->getCallBackDetails($entityArr, $input),
            'config_id'         => $configId,
            'config_version'    => '1',
            'diff'              => $this->getDiffForWorkflow($entityArr),
        ];

        $this->enrichFieldsForWorkflows($payload, $entity);

        return ['workflow' => $payload];
    }

    abstract public function getCallBackDetails(array $entityArr, array $input);

    abstract public function getDiffForWorkflow(array $entityArr);

    /**
     * @param array $content
     * @return array
     */
    public function transformActionResponse(array $content): array
    {
        if (empty($content) ||
            !isset($content['items']) ||
            !isset($content['items'][0]))
        {
            throw new \Exception("Malformed response received");
        }

        $payoutAction = $content['items'][0];

        return [
            Constants::ID                      => $payoutAction[Constants::ID] ?? null,
            Constants::WORKFLOW_ID             => $payoutAction[Constants::WORKFLOW_ID] ?? null,
            Constants::STATE_ID                => $payoutAction[Constants::STATE_ID] ?? null,
            Constants::ACTION_TYPE             => $payoutAction[Constants::ACTION_TYPE] ?? null,
            Constants::COMMENT                 => $payoutAction[Constants::COMMENT] ?? null,
            Constants::STATUS                  => $payoutAction[Constants::STATUS] ?? null,
            Constants::ACTOR_ID                => $payoutAction[Constants::ACTOR_ID] ?? null,
            Constants::ACTOR_TYPE              => $payoutAction[Constants::ACTOR_TYPE] ?? null,
            Constants::ACTOR_PROPERTY_KEY      => $payoutAction[Constants::ACTOR_PROPERTY_KEY] ?? null,
            Constants::ACTOR_PROPERTY_VALUE    => $payoutAction[Constants::ACTOR_PROPERTY_VALUE] ?? null,
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
    protected function enrichUserFieldsForActions(array &$input)
    {
        $actorInfo = self::getActorInfo();

        // todo: handle actor meta in case of admins
        $user = $this->ba->getUser();
        $actorName                       = ($user !== null) ? $user->getName() : "";
        $actorEmail                      = ($user !== null) ? $user->getEmail() : "";

        $input['actor_id']              = $actorInfo['actor_id'];
        $input['actor_type']            = $actorInfo['actor_type'];
        $input['actor_property_key']    = $actorInfo['actor_property_key'];
        $input['actor_property_value']  = $actorInfo['actor_property_value'];
        $input['service']               = Constants::SERVICE_RX . $this->ba->getMode();
        $input['actor_meta']            = ['email' => $actorEmail, 'name' => $actorName];
    }

    /**
     * Workflow system takes the config from the
     * workflow_entity_map table. This let's the merchant update the config
     * without affecting the existing workflows
     *
     * @param PublicEntity $entity
     * @return mixed
     */
    protected function fetchWorkflowConfigIdForEntity(PublicEntity $entity)
    {
        /* @var $workflowEntity Entity  */
        $workflowEntity = $this->repo->workflow_entity_map->findByEntityIdAndEntityType(
            $entity->getEntityName(), $entity->getId());

        if (empty($workflowEntity) === true)
        {
            throw new \Exception("workflow was not processed via workflow service");
        }

        return $workflowEntity->getConfigId();
    }

    /**
     * Returns "internal"/service for cron auth,
     * Returns admin id/admin for admin auth
     * Returns user id or merchant id/user for proxy/private auth types
     *
     * @param BasicAuth $ba
     * @return mixed|string
     */
    public static function getActorInfo()
    {
        $ba = app('basicauth');

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
        else if ($ba->isCron() === true)
        {
            $actorId = Constants::INTERNAL_ACTOR_NAME;
            $actorType = Constants::SERVICE;
            $actorPropertyKey = Constants::NAME;
            $actorPropertyValue = Constants::SERVICE_RX . $ba->getMode();
        }
        else if ($ba->isStrictPrivateAuth() === true)
        {
            $actorId = $merchant->getId();
            $actorType = Constants::MERCHANT;
            $actorPropertyValue = Constants::API;
        }
        else if ($ba->isProxyAuth() === true)
        {
            $actorId = $user->getId();
            $actorType = Constants::USER;
            $actorPropertyValue = $ba->getUserRole();
        }
        else
        {
            throw new Exception\BadRequestException('illegal auth used to access workflow service');
        }

        return [
            'actor_id'              => $actorId,
            'actor_type'            => $actorType,
            'actor_property_key'    => $actorPropertyKey,
            'actor_property_value'  => $actorPropertyValue,
        ];
    }
}
