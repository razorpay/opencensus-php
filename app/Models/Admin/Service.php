<?php

namespace RZP\Models\Admin;

use Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Facades\Redis;

use RZP\Base\Repository;
use RZP\Constants\Mode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Jobs;
use RZP\Models;
use RZP\Exception;
use RZP\Base\Fetch;
use RZP\Jobs\EsSync;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Services\Dcs\Features\Utility;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Base\ConnectionType;
use RZP\Constants\AdminFetch;
use RZP\Models\Payment\Method;
use RZP\Jobs\SFMerchantPocUpdate;
use RZP\Jobs\SFMerchantPocAsync;
use RZP\Models\User\Core as UserCore;
use RZP\Services\Mozart as MozartBase;
use RZP\Models\GeoIP\Service as GeoIP;
use RZP\Models\Feature as FeatureModel;
use RZP\Models\Admin\Admin as AdminModel;
use RZP\Models\User\Service as UserService;
use RZP\Jobs\SFAllMerchantToUnclaimedGroup;
use RZP\Constants\Entity as EntityConstants;
use RZP\Reconciliator\ReconSummary\DailyReconStatusSummary;
use RZP\Models\Base\QueryCache\Constants as QueryCacheConstants;
use RZP\Models\{Admin\Permission\Name, Base, Base\EsRepository, Base\UniqueIdEntity, Batch, Admin\Org, Pricing\Feature};

class Service extends Base\Service
{
    use Base\RepositoryUpdateTestAndLive;

    const FROM_MODE                  = 'from_mode';
    const TO_MODE                    = 'to_mode';
    const FIELDS_TO_SYNC             = 'fields_to_sync';
    const WHATSAPP_ENTITY_PREFIX_REG = '/^whatsapp_/';
    const SENSITIVE_KEYS_TO_BE_REDACTED = ['email', 'contact', 'customer_email', 'customer_contact'];

    public function getAllEntities($input, $isExternalAdmin = false)
    {
        $fields = AdminFetch::fields();

        $entities = AdminFetch::entities();

        // Fetching all entities and fill them with null
        $allEntities = array_fill_keys(Entity::getAllEntities(), null);

        $externalEntities = [];

        if ($this->app['basicauth']->getOrgType() === Org\Entity::RESTRICTED)
        {
            $entities = $this->getRestrictedEntities($entities);

            $allEntities = array_only($allEntities, AdminFetch::$restrictedEntities);
        }
        else
        {
            $externalEntities = AdminFetch::externalEntities();
        }

        $mergedEntities = array_merge($allEntities, $entities, $externalEntities);

        if ($isExternalAdmin === true)
        {
            $mergedEntities = AdminFetch::filterEntitiesForExternalAdmin($mergedEntities);
        }

        /** @var BasicAuth $basicAuth */
        $basicAuth  = app('basicauth');
        $adminRoles = $basicAuth->getPassport()['roles'] ?? [];
        if (empty($adminRoles) === true)
        {
            $this->trace->info(TraceCode::TENANT_ENTITY_NO_ADMIN_ROLES_SET);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        // For Razorpay org admins, run a tenant role-based filter
        if ($basicAuth->getAdmin()->getOrgId() === Org\Entity::RAZORPAY_ORG_ID)
        {
            $mergedEntities = AdminFetch::filterEntitiesByRole($mergedEntities, $adminRoles);
        }

        return [
            'version'   => 1,
            'fields'    => $fields,
            'entities'  => $mergedEntities
        ];
    }

    public function getAllEntitiesAxisAdmin($input)
    {
        $fields = AdminFetch::fields();

        $entities = AdminFetch::entities();

        // Fetching all entities and fill them with null
        $allEntities = array_fill_keys(Entity::getAllEntities(), null);

        $mergedEntities = array_merge($allEntities, $entities);

        $mergedEntities = AdminFetch::filterEntitiesForAxisAdmin($mergedEntities);

        return [
            'version'   => 1,
            'fields'    => $fields,
            'entities'  => $mergedEntities
        ];
    }

    /**
     * Filter through the entities and return only the allowed entities
     * and their allowed attributes for restricted orgs
     *
     * @param  array $entities
     * @return array
     */
    protected function getRestrictedEntities(array $entities): array
    {
        // Only allow entities that are open to restricted orgs
        $entities = array_only($entities, AdminFetch::$restrictedEntities);

        // Filter select entity attributes open to restricted orgs
        array_walk($entities, function (&$entity, $name)
        {
            // Get allowed attributes from respective Fetch class
            $fetchClass = Entity::getEntityNamespace($name) . '\\' . 'Fetch';

            $accesses = constant($fetchClass . '::ADMIN_RESTRICTED_ACCESSES');

            $entity = array_only($entity, $accesses);
        });

        return $entities;
    }

    public function syncEntityById(string $entity, string $id, array $input): array
    {
        Entity::validateEntityOrFailPublic($entity);

        (new Validator)->validateInput('sync_entity', $input);

        Mode::validateModeOrFailPublic($input[self::FROM_MODE]);

        $fromMode = $input[self::FROM_MODE];

        Mode::validateModeOrFailPublic($input[self::TO_MODE]);

        $toMode = $input[self::TO_MODE];

        $entityFrom = $this->repo->$entity->connection($fromMode)->findOrFailPublic($id);

        if (!(method_exists($this->repo->$entity, 'entityShouldSync') && $this->entityShouldSync($entityFrom)))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ONLY_SYNCED_ENTITIES_CAN_BE_SYNCED, null, null, 'requested entity is not a synced entity');
        }

        $entityTo = $this->repo->$entity->connection($toMode)->findOrFailPublic($id);

        $entityFieldsBefore = array();
        $entityFieldsAfter = array();

        foreach ($input[self::FIELDS_TO_SYNC] as $column)
        {
            if ($entityTo->hasAttribute($column))
            {
                $entityFieldsBefore[$column] = $entityTo->getAttribute($column);
                $entityTo->setAttribute($column, $entityFrom->getAttribute($column));
                $entityFieldsAfter[$column] = $entityTo->getAttribute($column);
            }
            else
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_FIELD_SENT, null, null, 'given field is an invalid field');
            }
        }

        $data1 = [
            'entity' => $entity,
            'mode'   => $toMode,
            'fieldsBeforeSync' => $entityFieldsBefore
        ];

        $this->app['trace']->info(TraceCode::ENTITIES_BEFORE_SYNC, $data1);

        $this->app['workflow']
            ->setEntityAndId($entityTo->getEntity(), $entityTo->getId())
            ->setInput($input)
            ->setOriginal([])
            ->setDirty($input)
            ->handle(null, null);

        $entityTo->setConnection($toMode);

        $entityTo->saveOrFail();

        $this->repo->$entity->syncToEs($entityTo, EsRepository::UPDATE, null, $toMode);

        $data2 = [
            'entity' => $entity,
            'mode'   => $toMode,
            'fieldsAfterSync' => $entityFieldsAfter
        ];

        $this->app['trace']->info(TraceCode::ENTITIES_AFTER_SYNC, $data2);

        return [
            'success' => true
        ];
    }

    public function fetchEntityById(string $entity, string $id, array $input = [], $isExternalAdmin = false): array
    {
        $data = ["function" => "fetchEntityById", "entity" => $entity];

        $this->app['trace']->info(TraceCode::FETCH_ENTITY_BY_ID, $data);

        list($isWhatsappInfra, $entityType) = $this->checkToUseWhatsappInfra($entity);

        $this->validateEntityTypeForRestrictedOrg($entity);

        if ($isExternalAdmin === true)
        {
            (new Validator)->validateEntityTypeForExternalAdmin($entityType);
        }

        // Run tenant role-based validation for Razorpay org admins only
        /** @var BasicAuth $basicAuth */
        $basicAuth  = app('basicauth');
        if (($basicAuth->isAdminAuth() === true) and
            ($basicAuth->getAdmin()->getOrgId() === Org\Entity::RAZORPAY_ORG_ID))
        {
            $this->validateEntityAccess($entity);
        }

        $retEntity = $this->handleExternalEntity($entity, $input, $id);

        if (empty($retEntity) === false)
        {
            return $retEntity;
        }

        if ($isWhatsappInfra === true)
        {
            $entity = $this->fetchEntityByNameAndId($entity, $id, $input, ConnectionType::RX_WHATSAPP_LIVE);
        }
        else if ( $entity === Entity::PAYMENT && isset($input['contact']))
        {
            $entity = $this->fetchEntityByNameAndId($entity, $id, $input, $this->repo->payment->getPaymentFetchReplicaConnection());
        }
        else if ( $entity === Entity::PAYMENT OR $entity === Entity::ORDER )
        {
            $entity = $this->fetchEntityByNameAndId($entity, $id, $input, ConnectionType::DATA_WAREHOUSE_ADMIN);
        }
        else
        {
            $entity = $this->fetchEntityByNameAndId($entity, $id, $input, ConnectionType::REPLICA);
        }

        $response = $entity->toArrayAdmin();

        // If entity is restricted and org has the feature flag (axis_org),
        // then use the fields allowed for that feature
        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === false)
        {
            $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            if ( (in_array($entity, AdminFetch::$restrictedEntities, true) === true) and
                ((new Org\Service)->validateOrgIdWithFeatureFlag($orgId, 'axis_org')) )
            {
                $response = (new Payment\Entity)->toArrayAdminRestrictedWithFeature($response, null, 'axis_org');
            }
        }

        if ($isExternalAdmin === true)
        {
            $response = AdminFetch::filterAttributesForExternalAdminFetchEntityById($entityType, $response);
        }

        return $response;
    }

    public function fetchEntityByIdForAxisRupayAdmin(string $entityType, string $id, array $input = [], $isExternalAdmin = false): array
    {
        $data = ["function" => "fetchEntityById", "entity" => $entityType];

        $this->app['trace']->info(TraceCode::FETCH_ENTITY_BY_ID, $data);

        $this->validateEntityTypeForRestrictedOrg($entityType);

        if ($isExternalAdmin === true)
        {
            (new Validator)->validateEntityTypeForExternalAdmin($entityType);
        }

        $retEntity = $this->handleExternalEntity($entityType, $input, $id);

        if (empty($retEntity) === false)
        {
            return $retEntity;
        }

        $entity = $this->fetchEntityByNameAndId($entityType, $id, $input, ConnectionType::REPLICA);

        $orgId = $this->app['basicauth']->getOrgId();

        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $response = $entity->toArrayAdmin();

        $response = $entity->toArrayAdminRestrictedWithFeature($response, null, 'axis_org');

        return $response;
    }

    public function checkToUseWhatsappInfra(string &$entity)
    {
        if (preg_match(self::WHATSAPP_ENTITY_PREFIX_REG, $entity) == 1)
        {
            $entity = substr($entity, 9);

            return [true, $entity];
        }

        return [false, $entity];
    }

    /**
     * Handle external entity fetch post validating for non-restricted org
     *
     * @param  string $entity
     * @param  array $input
     * @param  string|null $id
     *
     * @return null
     */
    protected function handleExternalEntity(string $entity, array $input, string $id = null)
    {
        // Check and handle external entities if non-restricted orgs
        if ($this->app['basicauth']->getOrgType() !== Org\Entity::RESTRICTED)
        {
            if (Entity::validateExternalServiceEntity($entity) === true)
            {
                try
                {
                    if($this->app['api.route']->isWDAServiceRoute() === true)
                    {
                        $this->trace->info(TraceCode::WDA_HANDLE_EXTERNAL_ENTITY, [
                            'input_params'    => $input,
                            'entity_name'     => $entity,
                            'route_auth'      => $this->auth->getAuthType(),
                            'route_name'      => $this->app['api.route']->getCurrentRouteName(),
                        ]);
                    }
                }
                catch(\Throwable $ex)
                {
                    $this->trace->error(TraceCode::WDA_SERVICE_LOGGING_ERROR, [
                        'error_message'    => $ex->getMessage(),
                        'route_name'       => $this->app['api.route']->getCurrentRouteName(),
                    ]);
                }

                $class = Entity::getExternalServiceClass($entity);

                $entityName = Entity::getExternalEntityName($entity, $class);

                if (empty($id) === true)
                {
                    return $class->fetchMultiple($entityName, $input);
                }
                else
                {
                    return $class->fetch($entityName, $id, $input);
                }
            }
        }

        return null;
    }

    public function fetchTerminalEntityByIdWithFlag($entity, $id, $subMerchantFlag = false)
    {
        $entity = $this->fetchEntityByNameAndId($entity, $id);

        $terminal = $entity->toArrayAdmin($subMerchantFlag);

        if ((new Org\Service)->validateEntityOrgId($terminal) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED, null, $terminal['id']);
        }

        return $terminal;
    }

    protected function fetchEntityByNameAndId(
        string $entity,
        string $id,
        array  $input = [],
        string $connectionType = null): Base\PublicEntity
    {
        $this->traceActiveDbConnections();

        Entity::validateEntityOrFailPublic($entity);

        $entityClass = Entity::getEntityClass($entity);

        $entityObject = new $entityClass;

        if ($entityObject->getIncrementing() === false)
        {
            $id = $entityClass::verifyIdAndSilentlyStripSign($id);
        }

        $entity = $this->repo->$entity->findOrFailByPublicIdWithParams($id, $input, $connectionType);

        $this->traceActiveDbConnections();

        return $entity;
    }

    protected function traceActiveDbConnections()
    {
        $activeDbConnection = array_keys(DB::getConnections());

        $this->trace->info(TraceCode::ACTIVE_DB_CONNECTIONS, $activeDbConnection);
    }

    /**
     * Removing current obsolete  admins from SME and Unclaimed group to avoid inconsistency
     * Fetching admins of SF_CLAIMED_SME_GROUP_ID
     * Calculate the difference of previous vs current AdminIDs
     * Removing admins from SF_CLAIMED_SME_GROUP_ID
     * Removing admins from SF_UNCLAIMED_GROUP_ID
     *
     * @param array $historicalSmeAdminsIds
     * @param array $currentSmeAdminIds
     */
    public function removeHistoricalAdminsFromGroup(array $historicalSmeAdminsIds, array $currentSmeAdminIds)
    {
        $deltaAdminIds = array_diff($historicalSmeAdminsIds, array_unique($currentSmeAdminIds));

        if (count($deltaAdminIds) > 0)
        {
            $smeGroup = $this->repo->group->findOrFailPublic(Group\Constant::SF_CLAIMED_SME_GROUP_ID);

            $unclaimed = $this->repo->group->findOrFailPublic(Group\Constant::SF_UNCLAIMED_GROUP_ID);

            $this->trace->info(TraceCode::REMOVAL_HISTORICAL_ADMINS_FROM_GROUP,
                               [
                                   'action'                 => 'removal_historical_admins_from_group',
                                   'historicalSmeAdminsIds' => $deltaAdminIds
                               ]
            );

            (new Admin\Core)->removeGroupsFromAdmins($smeGroup, $deltaAdminIds);

            (new Admin\Core)->removeGroupsFromAdmins($unclaimed, $deltaAdminIds);
        }
    }

    /**
     * @param array $value
     *
     * @return array
     */
    protected function getAdminIdsFromEmails(array $value): array
    {
        $currentAdmins = [];

        $ownerEmail = $value['Owner']['Email'];

        if (empty($value['Managers_In_Role_Hierarchy__c']) === true)
        {
            $adminEmails = [];
        }
        else
        {
            $emails = rtrim($value['Managers_In_Role_Hierarchy__c'], ',');

            $adminEmails = explode(',', $emails);
        }

        array_push($adminEmails, $ownerEmail);

        foreach ($adminEmails as $email)
        {
            // $adminId = $this->repo->admin->findByEmail($email)->getId();
            // hardcoding razorpay IN org id for now since all entities created in db are for RZP admins
            // this flow needs to be fixed with correct org coming in input for future use cases
            $adminId = $this->repo->admin->findByOrgIdAndEmail(Org\Entity::RAZORPAY_ORG_ID, $email)->getId();

            array_push($currentAdmins, $adminId);
        }

        return $currentAdmins;
    }

    public function redactInput($data, $sensitiveKeys)
    {
        foreach ($sensitiveKeys as $sensitiveKey)
        {
            if (array_key_exists($sensitiveKey, $data))
            {
                $data[$sensitiveKey] = '***';
            }
        }

        return $data;
    }

    public function fetchMultipleEntities($entity, $input, $isExternalAdmin = false)
    {
        $data = ["function" => "fetchMultipleEntities", "entity" => $entity, "input" => $this->redactInput($input, self::SENSITIVE_KEYS_TO_BE_REDACTED)];

        $this->app['trace']->info(TraceCode::FETCH_MULTIPLE_ENTITIES, $data);

        list($isWhatsappInfra, $entityType) = $this->checkToUseWhatsappInfra($entity);

        $this->traceActiveDbConnections();

        $this->validateEntityTypeForRestrictedOrg($entity);

        if ($isExternalAdmin === true)
        {
            $input = $this->preProcessInputForExternalAdminFetchMultipleEntities($input);

            $this->validateInputForExternalAdminFetchMultipleEntities($entityType, $input);
        }

        $entities = $this->handleExternalEntity($entity, $input);

        if ($entities !== null)
        {
            return $entities;
        }

        Entity::validateEntityOrFailPublic($entity);

        // Run tenant role-based validation for Razorpay org admins only
        /** @var BasicAuth $basicAuth */
        $basicAuth  = app('basicauth');
        if (($basicAuth->isAdminAuth() === true) and
            ($basicAuth->getAdmin()->getOrgId() === Org\Entity::RAZORPAY_ORG_ID))
        {
            $this->validateEntityAccess($entity);
        }

        if ($isWhatsappInfra === true)
        {
            $entities = $this->repo->$entity->fetch($input, null, ConnectionType::RX_WHATSAPP_LIVE);
        }
        else if ($entity === Entity::PAYMENT && isset($input['contact']))
        {
            $entities = $this->repo->$entity->fetch($input, null, $this->repo->payment->getPaymentFetchReplicaConnection());
        }
        else if ( $entity === Entity::PAYMENT OR $entity === Entity::ORDER )
        {
            $entities = $this->repo->$entity->fetch($input, null, ConnectionType::DATA_WAREHOUSE_ADMIN);
        }
        else if ($entity === Entity::BANKING_ACCOUNT_STATEMENT_POOL_ICICI or $entity === Entity::BANKING_ACCOUNT_STATEMENT_POOL_RBL)
        {
            $entities = $this->repo->$entity->fetch($input, null, ConnectionType::RX_ACCOUNT_STATEMENTS);
        }
        else
        {
            $entities = $this->repo->$entity->fetch($input, null, ConnectionType::REPLICA);
        }

        $this->traceActiveDbConnections();

        $response = $entities->toArrayAdmin();

        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === false)
        {
            $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            if (in_array($entity, AdminFetch::$restrictedEntities, true) === true and
                (new Org\Service)->validateOrgIdWithFeatureFlag($orgId, 'axis_org') )
            {
                foreach ($response['items'] as $index => $adminEntity)
                {
                    $response['items'][$index] = (new Payment\Entity)->toArrayAdminRestrictedWithFeature($adminEntity, null, 'axis_org');
                }
            }
        }

        if ($isExternalAdmin === true)
        {
            $response = AdminFetch::filterAttributesForExternalAdminFetchMultiple($entityType, $response);
        }

        return $response;
    }

    public function fetchAxisPaysecurePayments($entity, $input)
    {
        $this->app['trace']->info(TraceCode::FETCH_AXIS_PAYSECURE_PAYMENTS, [
                "function" => "fetchAxisPaysecurePayments",
                "entity" => $entity,
                "input" => $input
            ]);

        $this->validateEntityTypeForRestrictedOrg($entity);

        Entity::validateEntityOrFailPublic($entity);

        $entities = $this->repo->payment->fetchAxisPaysecurePayments($input);

        $response = new Base\PublicCollection();

        foreach ($entities as $e)
        {
            $paymentArray = $e->toArrayAdmin();

            $paymentArray['mode'] = $this->app['rzp.mode'];

            $paymentArray['entity'] = 'payment';

            $response->push($e->toArrayAdminRestrictedWithFeature($paymentArray, null, 'axis_org'));
        }

        return $response;
    }

    /**
     * Validates if an admin can access an entity, based on mapping
     * from EntityRoleScope
     *
     * @param string $entity
     *
     * @throws Exception\BadRequestException
     * @see EntityRoleScope
     */
    protected function validateEntityAccess(string $entity)
    {
        $entityRoles = EntityRoleScope::getEntityRoles($entity);

        if ($entityRoles === null)
        {
            $this->trace->info(TraceCode::TENANT_ENTITY_ROLES_NOT_MAPPED, ['entity' => $entity]);
            return;
        }

        /** @var BasicAuth $basicAuth */
        $basicAuth  = app('basicauth');
        $adminRoles = $basicAuth->getPassport()['roles'];
        if (empty($adminRoles) === true)
        {
            $this->trace->info(TraceCode::TENANT_ENTITY_NO_ADMIN_ROLES_SET,
                               ['entity' => $entity, 'entity_roles' => $entityRoles]);
            return;
        }

        if (count(array_intersect($entityRoles, $adminRoles)) === 0)
        {
            $this->trace->info(TraceCode::TENANT_ENTITY_ACCESS_DENIED,
                                ['entity' => $entity, 'entity_roles'=> $entityRoles, 'admin_roles' => $adminRoles]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
    }

    protected function validateEntityTypeForRestrictedOrg(string $entity)
    {
        if ($this->app['basicauth']->getOrgType() === Org\Entity::RESTRICTED)
        {
            // Only allow entities that are open to restricted orgs
            if (in_array($entity, AdminFetch::$restrictedEntities, true) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
            }
        }
    }

    public function sendTestNewsletter($input)
    {
        (new Validator)->validateInput('send_test_newsletter', $input);

        $mailer = new Newsletter(
            $input['subject'],
            $input['msg'],
            $input['template']
        );

        $mailer->setTestEmail($input['email']);

        return $mailer->send();
    }

    public function sendNewsletter($input)
    {
        (new Validator)->validateInput('send_newsletter', $input);

        $mailer = new Newsletter(
            $input['subject'],
            $input['msg'],
            $input['template']
        );

        $mailer->setRecipient($input['lists']);

        return $mailer->send();
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     */
    public function setConfigKeys(array $input): array
    {
        (new Validator)->validateInput('set_config_keys', $input);

        if ($this->auth->isAdminAuth() === true)
        {
            $this->hasPermissionToSetConfigKey($input);
        }

        $result = [];

        foreach ($input as $key => $value)
        {
            if (str_contains($key,ConfigKey::DCS_READ_WHITELISTED_FEATURES) === true)
            {
                $result[] = $this->setDCSConfigKey($key, $value);
            }
            else
            {
                $result[] = $this->setConfigKey($key, $value);
            }
        }

        return $result;
    }

    /**
     * @param array $input
     * @return array
     *
     * Set a single redis key.
     *
     * Sample input:
     *      key=merchant_enach_configs
     *      path=auth_gateway.8XGbgY6OnlIm6z
     *      value=esigner_legaldesk
     *
     * Currently it supports the below key:
     *  - `merchant_enach_configs`
     *       - This is to set the merchant specific rule to select
     *         the esigner gateway for enach.
     *       - Supported values are `esigner_legaldesk` and `esigner_digio`
     *       - Sample content of this:
     *          {
     *              "auth_gateway": {
     *                  "override": "esigner_legaldesk",
     *                  "8XGbgY6OnlIm6z": "esigner_legaldesk"
     *              }
     *          }
     *       - Here, when "override" is set, all the merchants would be forcefully
     *         redirected to that specific gateway, by overriring the merchant specific
     *         configurations.
     */
    public function updateConfigKey(array $input): array
    {
        (new Validator)->validateInput('update_config_key', $input);

        $currentConfig = null;

        $currentConfig = $this->app['cache']->get($input['key'], []);

        $oldConfig = $currentConfig;

        array_set($currentConfig, $input['path'], $input['value']);

        $data = [
            'key'       => $input['key'],
            'old_value' => $oldConfig,
            'new_value' => $currentConfig,
        ];

        $this->app['cache']->forever($input['key'], $currentConfig);

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    /**
     * @param string $key
     * @param mixed $newValue
     *
     * @return array
     */
    protected function setConfigKey(string $key, $newValue): array
    {
        $oldValue = Cache::get($key);

        Cache::forever($key, $newValue);

        $data = [
            'key'       => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ];

        if (ConfigKey::isSensitive($key) === false)
        {
            $this->trace->info(TraceCode::REDIS_KEY_SET, $data);
        }

        return $data;
    }

    /**
     * @param string $key
     * @param mixed $newValue
     *
     * @return array
     */
    protected function setDCSConfigKey(string $key, $newValue): array
    {
        $keys = [];
        $oldValue = Cache::get($key);

        // setting the key without prefix for the admin dashboard
        Cache::forever($key, $newValue);

        foreach (Utility::$cachePrefixes as $prefix)
        {
            $keys[] = $prefix . '_' .$key;

            Cache::forever($prefix . '_' .$key, $newValue);
        }

        $data = [
            'key'       => $keys,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ];

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    public function getConfigKeys(): array
    {
        $result = [];

        foreach (ConfigKey::PUBLIC_KEYS as $key)
        {
            $result[$key] = Cache::get($key);
        }

        return $result;
    }

    public function getConfigKey($input)
    {
        (new Validator)->validateInput('get_config_key', $input);

        $key = $input['key'];

        $config = $this->app['cache']->get($key, []);

        return $config;
    }

    public function deleteConfigKey($input): array
    {
        (new Validator)->validateInput('delete_config_key', $input);

        $currentConfig = null;

        $currentConfig = $this->app['cache']->get($input['key'], []);

        $oldConfig = $currentConfig;

        array_forget($currentConfig, $input['path']);

        $data = [
            'key'       => $input['key'],
            'old_value' => $oldConfig,
            'new_value' => $currentConfig,
        ];

        $this->app['cache']->forever($input['key'], $currentConfig);

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    public function getQueryCacheCounts(): array
    {
        $result = [];

        $cacheEvents = [
            QueryCacheConstants::CACHE_HITS,
            QueryCacheConstants::CACHE_MISSES,
            QueryCacheConstants::CACHE_WRITES,
            QueryCacheConstants::CACHE_FLUSHES,
        ];

        foreach (Entity::CACHED_ENTITIES as $entity => $_)
        {
            $result[$entity] = [];

            foreach ($cacheEvents as $event)
            {
                $cacheKey = $entity . '_' . $event;

                $result[$entity][$event] = (intval(Cache::get($cacheKey)) ?? 0);
            }
        }

        return $result;
    }

    public function generateScorecard(array $input)
    {
        try
        {
            if($this->app['api.route']->isWDAServiceRoute() === true)
            {
                $this->trace->info(TraceCode::WDA_ADMIN_SCORECARD, [
                    'input'         => $input,
                    'route_auth'    => $this->auth->getAuthType(),
                    'route_name'    => $this->app['api.route']->getCurrentRouteName(),
                ]);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_SERVICE_LOGGING_ERROR, [
                'error_message'    => $ex->getMessage(),
                'route_name'       => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $data = (new Scorecard)->generateScorecard($input);

        return $data;
    }

    public function generateBankingScorecard(array $input)
    {
        $bankingScoreCardObj = new BankingScorecard;

        $data = $bankingScoreCardObj->generateBankingScorecardData($input);

        $bankingScoreCardObj->sendMail($data);

        return ['success' => true];
    }

    public function processMailgunCallback($type, $input)
    {
        $validator = new Validator;

        $validator->setStrictFalse();

        $validator->validateInput('mailgun_webhook', $input);

        return (new Mailgun)->processCallback($type, $input);
    }

    public function processSetCronJobCallback(array $input)
    {
        $this->trace->info(TraceCode::SETCRONJOB_CALLBACK, $input);
    }

    public function updateTaxColumnValue(string $entity, int $limit = 10000)
    {
        if (in_array($entity, [Entity::PAYMENT, Entity::TRANSACTION]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid entity: ' . $entity);
        }

        $count = $this->repo->$entity->updateTax($limit);

        return ['count' => $count];
    }

    public function updateGeoIps(array $input)
    {
        return (new GeoIP)->updateGeoIps($input);
    }

    public function updateMdr()
    {
        Jobs\MdrBackFill::dispatch($this->mode);

        return [
            'success' => true,
        ];
    }

    public function dbMetaDataQuery(array $input): array
    {
        return (new Query\Core)->dbMetaDataQuery($input);
    }

    public function fetchReconciliationSummary(array $input)
    {
        $data = (new DailyReconStatusSummary)->generateReconSummary($input);

        return $data;
    }

    public function fetchHourlyReconciliationSummary(array $input)
    {
        $data = (new DailyReconStatusSummary)->generateReconSummaryByGateway($input);

        return $data;
    }

    public function createBatch(array $input)
    {
        $batchCore = new Batch\Core;

        $sharedMerchant = $this->repo->merchant->getSharedAccount();

        $batch = $batchCore->create($input, $sharedMerchant);

        return $batch->toArrayPublic();
    }

    public function validateFile(array $input)
    {
        $batchCore = new Batch\Core;

        $sharedMerchant = $this->repo->merchant->getSharedAccount();

        $batch = $batchCore->storeAndValidateInputFile($input, $sharedMerchant);

        return $batch;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function updateFromBatchService(array $input): array
    {
        $batchId = '';

        if ($this->app['basicauth']->isBatchApp() === true)
        {
            $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;
        }

        (new Validator)->validateInput('batch_admin_update', $input);

        $adminEntity = $this->repo->admin->findByPublicId($input[Batch\Header::ID]);

        unset($input[Batch\Header::ID]);

        $updatedAdmin = (new AdminModel\Service)->validateAndEditAdmin("admin_" . $adminEntity->getId(), $input, $batchId);

        return $updatedAdmin->toArrayPublic();
    }

    public function uploadFile(string $type, array $input)
    {
        $fileCore = new File\Core;

        $admin = $this->auth->getAdmin();

        $fileCore->uploadFile($admin, $type, $input);

        return ['success' => true];
    }

    public function updateEntityBalanceIdInBulk(string $entity, array $input): array
    {
        assertTrue(
            in_array(strtolower($entity), Entity::ENTITIES_WITH_BALANCE_ID_COLUMN),
            "Entity not whitelisted for this bulk operation - $entity");

        $limit            = (int) ($input['limit'] ?? 10000);
        $merchantIds      = $input['merchant_ids'] ?? [];
        $merchantIdsLimit = (int) ($input['merchant_limit'] ?? 5);

        // If no merchant ids provided in input, query in limit set of unique mids where balance_id is null.
        if (empty($merchantIds) === true)
        {
            $merchantIds = $this->repo->$entity->getUniqueMerchantIdsWhereBalanceIdIsNull($merchantIdsLimit);
        }

        // If still no merchant ids, this means no rows pending updatation.
        if (empty($merchantIds) === true)
        {
            return [];
        }

        $merchants = $this->repo->merchant->findMany($merchantIds);

        if ($merchants->count() === 0)
        {
            return [];
        }

        $this->trace->info(
            TraceCode::ENTITY_BULK_UPDATE_BALANCE_ID_REQUEST,
            compact('entity', 'limit', 'merchantIds', 'merchantIdsLimit'));

        $failedMerchantIds           = [];
        $totalUpdatedRowCounts       = 0;
        $perMerchantUpdatedRowCounts = [];

        foreach ($merchants as $merchant)
        {
            $merchantId = $merchant->getId();
            $balanceId  = $merchant->primaryBalance->getId();

            try
            {
                $updatedRowCounts = $this->repo->$entity->bulkUpdateBalanceId($merchantId, $balanceId, $limit);

                $totalUpdatedRowCounts += $updatedRowCounts;
                $perMerchantUpdatedRowCounts[$merchantId] = $updatedRowCounts;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ENTITY_BULK_UPDATE_BALANCE_ID_ERROR,
                    compact('entity', 'merchantId', 'balanceId'));

                $failedMerchantIds[] = $merchantId;
            }
        }

        return compact(
            'merchantIds',
            'failedMerchantIds',
            'totalUpdatedRowCounts',
            'perMerchantUpdatedRowCounts');
    }

    public function setRedisKeys(array $input): array
    {
        (new Validator)->validateInput('set_redis_keys', $input);

        $redis = Redis::Connection('mutex_redis');

        $result = [];

        foreach ($input as $key => $value)
        {
            $values = array_map(function($val) {
                return strtolower($val);
            }, $value);

            $values = array_change_key_case($values, CASE_LOWER);

            $result[] = $this->setRedisKey($redis, $key, $values);
        }

        return $result;
    }

    public function setRedisKey($redis, string $key, array $values): array
    {
        if(empty($values) === false)
        {
            $redis->HMSET($key, $values);
        }

        $data = [
            'key'   => $key,
            'value' => $values,
        ];

        $this->trace->info(TraceCode::REDIS_KEY_SET, $data);

        return $data;
    }

    public function setGatewayDowntimeConf(array $input): array
    {
        (new Validator)->validateInput('set_gateway_downtime_redis_keys', $input);

        $redis = $this->app['redis']->connection('mutex_redis');

        foreach ($input[ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2] as $value)
        {
            $values[$value['key']] = json_encode($value['value']);
        }

        $values = array_change_key_case($values, CASE_LOWER);

        $this->setRedisKey($redis, ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2, $values);

        // Now get configuration for all the gateways and return
        return $this->getGatewayDowntimeConf();
    }

    public function getGatewayDowntimeConf(): array
    {
        $redis = $this->app['redis']->connection('mutex_redis');

        $result = [];

        $conf = $redis->HGETALL(ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2);

        foreach ($conf as $key => $value)
        {
            $result[] =
                [
                    'key'   => $key,
                    'value' => json_decode($value),
                ];
        }

        return [
            ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2 => $result,
        ];
    }

    public function sendTestSms(array $input)
    {
        $admin = $this->auth->getAdmin();

        $this->trace->info(
            TraceCode::ADMIN_SEND_TEST_SMS_REQUEST,
            [
                'admin_id'          => $admin->getId(),
                'payload'           => $input,
            ]);

        $ravenPayload = [
            'receiver' => $input['receiver'],
            'source'   => 'api.admin.test.sms',
            'gateway'  => $input['gateway'],
            'sender'   => $input['sender'],
            'template' => $input['template'] ?? 'sms.p2p.verification_completed',
            'params'   => $input['params'],
        ];

        return $this->app->raven->sendSms($ravenPayload, true);
    }

    /**
     * @param $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    public function bulkCreate($input)
    {
        (new Validator)->validateInput('bulkCreateEntity', $input);

        $type = $input['type'];

        Entity::validateEntityOrFailPublic($type);

        $dataList = $input['data'];

        $failed = $processed = [];

        $this->repo->transactionOnLiveAndTest(function() use ($type, $dataList, &$processed, &$failed) {
            foreach ($dataList as $data)
            {
                try
                {
                    $newEntityClass = (new Entity)->getEntityClass($type);

                    $newEntity = (new $newEntityClass)->generateId();

                    if (array_key_exists($newEntity::MERCHANT_ID, $data))
                    {
                        $merchantId = $data[$newEntity::MERCHANT_ID];

                        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                        $entityCore = (new Entity)->getEntityCoreClass($type);

                        if (method_exists($entityCore, 'deleteExistingEntities'))
                        {
                            $entityCore->deleteExistingEntities($merchant, $data);
                        }

                        $newEntity->merchant()->associate($merchant);

                        unset($data[$newEntity::MERCHANT_ID]);
                    }

                    $newEntity->build($data);

                    $this->repo->saveOrFail($newEntity);

                    array_push($processed, $newEntity[$newEntity::ID]);

                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR
                    );
                    array_push($failed, $data);
                }
            }
        });

        $summary = [
            'failed_count'  => count($failed),
            'success_count' => count($processed),
            'failed'        => $failed,
        ];

        $this->trace->info(TraceCode::ENTITY_BULK_ADD_REQUEST,
                           [
                               'summary' => $summary,
                               'processed' => $processed,
                           ]
        );

        return $summary;
    }

    public function getPvtResponse($input)
    {
        (new Validator)->validateInput('mozart_gateway_pvt', $input);

        $payload = $input['payload']['entities'];

        if (array_key_exists("attempt", $payload) === true && array_key_exists("amount", $payload['attempt']) === true)
        {
            $amount = (int)($payload['attempt']['amount']);

            if($amount > 1)
            {
                return ["Not authorized for PVT more than amount 1.00"];
            }
        }

        try
        {
            $this->trace->info(
                TraceCode::MOZART_ACTION_INIT,
                [
                    'namespace' => $input['namespace'],
                    'gateway'   => $input['gateway'],
                    'action'    => $input['action']
                ]);
            $response = (new MozartBase($this->app))->sendMozartRequest($input['namespace'],
                $input['gateway'],
                $input['action'],
                $payload,
                $input['version']);

            $this->trace->info(TraceCode::MOZART_ACTION_COMPLETED, $response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MOZART_ACTION_FAILED);

            $response = 'Caught Exception: ' . $e->getMessage();
        }

        return $response;
    }

    public function getModeConfigInstruments(): array
    {
        $result = [];
        $result['method'] = Method::getAllPaymentMethods();
        $result['card_type'] = Card\Type::getCardTypes();
        $result['issuer'] = Card\Issuer::getAllIssuers();
        $result['network_code'] = Card\Network::getAllNetworkCodes();
        return $result;
    }


    /**
     * @param array $input
     * @param array $currentSmeAdminIds
     * @param array $currentClaimedMerchantsIds
     */
    public function merchantPocUpdateOperation(array $input, array &$currentSmeAdminIds, array &$currentClaimedMerchantsIds)
    {
        (new Validator)->validateInput('sf_poc_data', $input);

        foreach ($input['records'] as $key => $value)
        {
            try
            {
                (new Validator())->validateInput('sf_poc_record', $value);

                $recordAdminIds = $this->getAdminIdsFromEmails($value);

                $merchantId = $value['Merchant_ID__c'];

                $noOfMerchantIds = $this->repo->merchant->fetchLinkedAccountsCount($merchantId);

                // Skipping Merchants associated account who has more than 2000 associated accounts this is temporary
                if ($noOfMerchantIds > 2000)
                {
                    $merchantIds = [];

                    $this->trace->info(TraceCode::SF_POC_MERCHANT_LINKED_SKIPPED,
                                       [
                                           'message'    => 'Merchant POC Linked account skipped for Below merchants inside merchantPocUpdateOperation',
                                           'merchantId' => $merchantId
                                       ]
                    );
                }
                else
                {
                    $merchantIds = $this->repo->merchant->fetchLinkedAccountMids($merchantId);
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::SF_POC_UPDATE_DATA_VALIDATION_ERROR, [
                    'message' => 'Error in record data',
                    'record'  => $value,
                ]);

                continue;
            }

            $tagNames = strtolower($value['Owner_Role__c']);

            if (strpos($tagNames, 'sme') !== false)
            {
                $currentSmeAdminIds = array_merge($recordAdminIds, $currentSmeAdminIds);
            }

            $merchantIds[] = $merchantId;

            SFMerchantPocUpdate::dispatch($this->mode, $value, $recordAdminIds);

            $currentClaimedMerchantsIds = array_merge($merchantIds, $currentClaimedMerchantsIds);

            $merchantIdsBatches = array_chunk($merchantIds, 1000, true);

            foreach ($merchantIdsBatches as $batch)
            {
                EsSync::dispatch($this->mode, EsRepository::UPDATE, Entity::MERCHANT, $batch);
            }
        }
    }

    /**
     * @param array $input
     * @param array $currentSmeAdminIds
     * @param array $currentClaimedMerchantsIds
     * @param int   $timeStamp
     * @param bool  $timeBased
     *
     * Either Fetch SF data from client or use passed data while testing
     */
    public function fetchAndDispatchPocOperation(array &$input, array &$currentSmeAdminIds, array &$currentClaimedMerchantsIds, int $timeStamp = 0, bool $timeBased = false)
    {
        if (empty($input) === true)
        {
            if ($timeStamp > 0 or $timeBased)
            {
                $input = $this->app->salesforce->fetchAccountDetails($nextUrl = '', $timeStamp, $timeBased);
            }
            else
            {
                $input = $this->app->salesforce->fetchAccountDetails();
            }

            $this->merchantPocUpdateOperation($input, $currentSmeAdminIds, $currentClaimedMerchantsIds);

            while ($input['done'] === false)
            {
                $input = $this->app->salesforce->fetchAccountDetails($input['nextRecordsUrl']);

                $this->merchantPocUpdateOperation($input, $currentSmeAdminIds, $currentClaimedMerchantsIds);
            }
        }
        else
        {
            $this->merchantPocUpdateOperation($input, $currentSmeAdminIds, $currentClaimedMerchantsIds);
        }
    }

    /**
     * @param $input
     *
     * @throws \Throwable
     */
    public function updateMerchantPoc($input)
    {
        RuntimeManager::setMemoryLimit('2048M');

        // As request time more than 60 second
        RuntimeManager::setTimeLimit(180);

        $startTime = millitime();

        $historicalSmeAdminsIds = \DB::connection($this->mode)->table('group_map')
                                     ->select('entity_id')
                                     ->where('group_id', Group\Constant::SF_CLAIMED_SME_GROUP_ID)
                                     ->get()
                                     ->pluck('entity_id')
                                     ->toArray();

        $historicalClaimedMerchantIds = $this->repo->merchant->fetchHistoricalClaimedMerchantIds($this->mode);

        SFMerchantPocAsync::dispatch($this->mode, $input, $historicalSmeAdminsIds, $historicalClaimedMerchantIds);

        $this->trace->info(TraceCode::MERCHANT_POC_UPDATE_TIME_DURATION,
                           [
                               'time_taken'                   => millitime() - $startTime,
                               'historicalSmeAdminsIds'       => count($historicalSmeAdminsIds),
                               'historicalClaimedMerchantIds' => count($historicalClaimedMerchantIds)
                           ]);
    }

    public function unclaimedMerchantPoc(array $input = [])
    {
        RuntimeManager::setTimeLimit(10000);

        $input['count'] = 5000;

        $input['afterId'] = $input['afterId'] ?? null;

        $count = 0;

        while (true)
        {
            $merchants = (new Merchant\Core)->getAllMerchantIds($input);

            if ($merchants->isEmpty() === true)
            {
                break;
            }

            $count += $merchants->count();

            $input['afterId'] = $merchants->last()->getId();

            $this->trace->info(TraceCode::MERCHANT_POC_UPDATE_REQUEST,
                               [
                                   'message' => 'Merchant POC update request for Unclaimed From Service before Job',
                                   'count'   => $count,
                                   'startId' => $merchants->first()->getId(),
                                   'endId'   => $merchants->last()->getId(),
                               ]
            );

            SFAllMerchantToUnclaimedGroup::dispatch($this->mode,
                                                    $merchants->pluck(Merchant\Entity::ID)->toArray());
            EsSync::dispatch($this->mode, EsRepository::UPDATE, Entity::MERCHANT,
                             $merchants->pluck(Merchant\Entity::ID)->toArray());
        }

        return [];
    }


    public function updateMerchantPocWithTimeStamp(array $input = [])
    {
        $timeStamp = $input['timeStamp'] ?? 0;

        unset($input['timeStamp']);

        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit(20000);

        $currentClaimedMerchantsIds = [];

        $currentSmeAdminIds = [];

        $this->fetchAndDispatchPocOperation($input, $currentSmeAdminIds, $currentClaimedMerchantsIds, $timeStamp, true);

        EsSync::dispatch($this->mode, EsRepository::UPDATE, Entity::MERCHANT, array_unique($currentClaimedMerchantsIds));
    }

    /**
     *
     * For each config key which the admin wants to modify,
     * the following is done iteratively :
     *  1. Fetch permissions required to modify the key from redis_config_map
     *  2. Add 'update_config_key' as a default permission to this set of
     *  required permissions.
     *  3. If admin does not have even one of the required permissions obtained in
     *  the step above, an exception is thrown implying denial of access.
     * If no exception is thrown for any key, it implies that validation has succeeded
     * and that the admin can change all the config keys that he/she has mentioned.
     *
     * @param array $input
     * @throws Exception\BadRequestException
     */
    public function hasPermissionToSetConfigKey(array $input)
    {
        $adminPermissions = $this->auth->getAdmin()->getPermissionsList();

        foreach ($input as $key => $value)
        {
            $requiredPermissions = ConfigKey::fetchPermissionsForKey($key);

            // Admins with this permission should be able to alter any key
            // This permission should be given only to select admins
            $requiredPermissions []= Name::UPDATE_CONFIG_KEY;

            if (empty(array_intersect($requiredPermissions, $adminPermissions)) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ACCESS_DENIED,
                    null,
                    [
                        'admin_id'             => $this->auth->getAdmin()->getPublicId(),
                        'required_permissions' => $requiredPermissions,
                        'config_keys'          => array_keys($input)
                    ]);
            }
        }

    }

    protected function preProcessInputForExternalAdminFetchMultipleEntities($input)
    {
        $input[Fetch::COUNT] = $input[Fetch::COUNT] ?? AdminFetch::EXTERNAL_ADMIN_FETCH_MULTIPLE_ENTITIES_MAX_COUNT;

        $input[Fetch::COUNT] = min($input[Fetch::COUNT], AdminFetch::EXTERNAL_ADMIN_FETCH_MULTIPLE_ENTITIES_MAX_COUNT);

        unset($input[Fetch::SKIP]);

        return $input;
    }

    protected function validateInputForExternalAdminFetchMultipleEntities($entityType, $input)
    {
        $validator = new Validator;

        $validator->validateEntityTypeForExternalAdmin($entityType);

        $validator->validateInput('external_admin_fetch_multiple_' . $entityType, $input);

    }

    private function getPayloadFromConfigs($configs,$refund_speed):array
    {
        $config = json_decode($configs["config"],true);

        $manual_expiry_period_set = isset($config["capture_options"]["manual_expiry_period"]);

        return [
            "id"       =>  "config_" . $configs["id"],
            "config" => [
                "capture"  => $config["capture"],
                "capture_options" => [
                    "refund_speed" => $refund_speed,
                    "automatic_expiry_period" =>  $config["capture_options"]["automatic_expiry_period"],
                    "manual_expiry_period"    =>  $manual_expiry_period_set ? $config["capture_options"]["manual_expiry_period"] : null,
                ]
            ],
            "type" => $configs->getType(),
        ];
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function enableInstantRefunds($id, array $input): array
    {
        $featureParams = [
            Models\Feature\Entity::ENTITY_ID   => $id,
            Models\Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Models\Feature\Entity::NAMES       => $input["features"],
            Models\Feature\Entity::SHOULD_SYNC => $input["should_sync"],
        ];

        $this->trace->info(TraceCode::FEATURE_PARAMS_INSTANT_REFUNDS,
            [
                'Feature Params'                 => $featureParams
            ]
        );

        $res = (new Models\Feature\Service)->addFeatures($featureParams,"accounts",$id);


        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $responseEntity = (new Merchant\Core)->editConfig($merchant,["default_refund_speed"=>"optimum"]);


        $response = [];

        $configs = (new Models\Payment\Config\Service)->repo->config->fetchConfigByMerchantIdAndType($id, 'late_auth');

        $this->trace->info(TraceCode::CONFIG_FETCH_FOR_REFUNDS,
            [
                'Configs'                        => $configs
            ]
        );

        $payload = $this->getPayloadFromConfigs($configs[0],"optimum");

        array_push($response,(new Models\Payment\Config\Core)->withMerchant($merchant)->update($payload));


        return $response;

    }

    /**
     * @throws Exception\BadRequestException
     */
    public function disableInstantRefunds($id,array $input): array
    {

        $res = (new Models\Feature\Service)->deleteEntityFeature("accounts",$id,$input["features"][0],$input);


        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $responseEntity =  (new Merchant\Core)->editConfig($merchant,["default_refund_speed"=>"normal"]);


        $response = [];

        $configs = (new Models\Payment\Config\Service)->repo->config->fetchConfigByMerchantIdAndType($id, 'late_auth');

        $payload = $this->getPayloadFromConfigs($configs[0],"normal");

        array_push($response,(new Models\Payment\Config\Core)->withMerchant($merchant)->update($payload));


        return $response;
    }

    /**
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function toggleWhatsappNotification(string $id,array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $user = $merchant->primaryOwner();

        $this->trace->info(TraceCode::ADMIN_USER_FETCH,
            [
                'user id'   =>  $user->getId(),
            ]
        );

        if ($input["enable"] == true)
        {
            $featureParams = [
                FeatureModel\Entity::ENTITY_ID => $id,
                FeatureModel\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                FeatureModel\Entity::NAMES => ['axis_whatsapp_enable'],
                FeatureModel\Entity::SHOULD_SYNC => true,
            ];

            $response = (new FeatureModel\Service)->addFeatures($featureParams, "accounts", $id);

            array_merge($response, (new UserService)->optInForWhatsapp(["source"=>$input["source"]],$user));

            return $response;
        }

        $response = (new FeatureModel\Service)->deleteEntityFeature("accounts",$id,'axis_whatsapp_enable',[FeatureModel\Entity::SHOULD_SYNC => true]);

        array_merge($response, (new UserService)->optOutForWhatsapp(["source"=>$input["source"]],$user));

        return $response;

    }

    // for fetching the experiment
    protected function isExperimentEnabled($experiment)
    {
        $app = $this->app;

        $variant = $app['razorx']->getTreatment(UniqueIdEntity::generateUniqueId(),
            $experiment, $app['basicauth']->getMode() ?? Mode::LIVE);

        $this->trace->info(TraceCode::REARCH_TIDB_EXPERIMENT_VARIANT, [
            'variant' => $variant,
            'experiment' => $experiment,
        ]);

        return ($variant === 'on');
    }

}
