<?php

namespace RZP\Models\Base\Audit;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as CommonEntity;

class Core extends Base\Core
{
    const DATA_LAKE_ENTITY_AUDIT_QUERY  = "select audit.changelog,info.meta,audit.timestamp as modified_at,audit.created_date from hive.realtime_entity_meta.audit audit inner join hive.realtime_hudi_api.audit_info info on info.id = audit.audit_id where audit.service='api' and audit.entity_type = '%s' and audit.producer_created_date<='%s' and audit.entity_id='%s' and audit.timestamp < %s limit %s";

    const DATA_LAKE_AUDIT_QUERY = "select audit.timestamp as timestamp, audit.entity_type as category, audit.op_type as action, audit.old_data as old_value, audit.changelog as new_value, info.meta from hive.realtime_entity_meta.audit audit inner join hive.realtime_hudi_api.audit_info info on info.id = audit.audit_id where audit.service = 'api' and audit.entity_id in ('%s') and audit.entity_type in ('%s') and audit.timestamp between %s and %s order by audit.timestamp desc";

    const MERCHANT_AUDIT_ENTITIES = ['merchants', 'merchant_business_details',
                                     'stakeholders', 'merchant_details',
                                     'merchant_verification_details',
                                     'merchant_promotions', 'merchant_documents',
                                     'users', 'merchant_websites', 'merchant_consents', 'pricing'];

    /**
     * @var array
     */
    private $userActors;

    /**
     * @var array
     */
    private $adminActors;

    function __construct() {
        parent::__construct();
        $this->adminActors = [];
        $this->userActors = [];
    }

    public function create()
    {
        $auditInfo = new Entity;

        $auditInfo->generateId();

        $facadeRoot = App::getFacadeRoot();

        $ba = $facadeRoot['basicauth'];

        $request = $facadeRoot['request'];

        $trace = $facadeRoot['trace'];

        [$actorId, $actorType] = $this->getActorIdAndType();

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip();

        $meta = [
            Constants::ACTOR_ID   => $actorId,
            Constants::ACTOR_TYPE => $actorType,
            Constants::AUTH_TYPE  => $ba->getAuthType(),
            Constants::APP        => $ba->getInternalApp() ?? null,
            Constants::TASK_ID    => $request->getTaskId(),
            Constants::IP         => $clientIpAddress ?? null
        ];

        $auditInfo->setMeta($meta);

        $auditInfo->setConnection(Mode::LIVE);

        $auditInfo->saveOrFail();

        return $auditInfo;
    }

    protected function getActorIdAndType()
    {
        $facadeRoot = App::getFacadeRoot();

        $ba = $facadeRoot['basicauth'];

        $admin = $ba->getAdmin();

        if ($admin !== null)
        {
            return [$admin->getId(), Constants::ACTOR_TYPE_ADMIN];
        }

        $user = $ba->getUser();

        if ($user !== null)
        {
            return [$user->getId(), Constants::ACTOR_TYPE_USER];
        }

        return ['', ''];
    }

    public function getMerchantAuditInfo(string $merchantId, int $timeStamp,int $limit ): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $users = (new \RZP\Models\Merchant\Core())->getUsers($merchant);

        $dataLakeQuery = sprintf(self::DATA_LAKE_ENTITY_AUDIT_QUERY,"merchants",date('Y-m-d', $timeStamp), $merchantId, $timeStamp,$limit);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $response['merchant'] = $lakeData;

        $dataLakeQuery = sprintf(self::DATA_LAKE_ENTITY_AUDIT_QUERY,'merchant_details',date('Y-m-d', $timeStamp), $merchantId, $timeStamp,$limit);

        $response['merchant_details'] = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $result = [];

        foreach ($users as $user)
        {
            try
            {
                $id = $user['id'];

                $dataLakeQuery = sprintf(self::DATA_LAKE_ENTITY_AUDIT_QUERY, "users",date('Y-m-d', $timeStamp),$id, $timeStamp,$limit);

                $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

                array_push($result, $lakeData);

            }
            catch (\Exception $e)
            {

            }

        }

        $response['users'] = $result;

        $result = [];

        $stakeholders = $this->repo->stakeholder->fetchStakeholders($merchantId);

        if ($stakeholders->isNotEmpty() === true)
        {
            foreach ($stakeholders as $stakeholder)
            {
                try
                {
                    $id = $stakeholder->getId();

                    $dataLakeQuery = sprintf(self::DATA_LAKE_ENTITY_AUDIT_QUERY,"stakeholders",date('Y-m-d', $timeStamp), $id, $timeStamp,$limit);

                    $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

                    array_push($result, $lakeData);

                }
                catch (\Exception $e)
                {

                }

            }

            $response['stakeholders'] = $result;

        }

        return $response;

    }

    public function getAuditInfo(string $entity,string $id, int $timeStamp,int $limit): array
    {
        $dataLakeQuery = sprintf(self::DATA_LAKE_ENTITY_AUDIT_QUERY,$entity,date('Y-m-d', $timeStamp),$id,$timeStamp,$limit);

        return $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

    }

    protected function getEntityIdsForMerchant(\RZP\Models\Merchant\Entity $merchant): array
    {
        /*
         Entity::MERCHANT,
         Entity::MERCHANT_BUSINESS_DETAIL,
         Entity::STAKEHOLDER,
         Entity::MERCHANT_DETAIL,
         Entity::MERCHANT_VERIFICATION_DETAIL,
         Entity::MERCHANT_PROMOTION,
         Entity::MERCHANT_DOCUMENT,
         Entity::USER,
         Entity::MERCHANT_WEBSITE,
         Entity::MERCHANT_CONSENTS
         */
        $merchantId = $merchant->getId();

        $entityIds = [];

        $entityIds[] = $merchantId;

        $entityIds[] = optional($merchant->merchantBusinessDetail)->getId();

        $entityIds = array_merge($entityIds, $this->repo->stakeholder->fetchStakeholders($merchantId)->pluck('id')->toArray());

        //$entityIds[] = optional($merchant->merchantDetail)->getId();

        $entityIds = array_merge($entityIds, $this->repo->merchant_verification_detail->getDetailsForMerchant($merchantId)->pluck('id')->toArray());

        $entityIds = array_merge($entityIds, $this->repo->merchant_promotion->getByMerchantId($merchantId)->pluck('id')->toArray());

        $entityIds = array_merge($entityIds, $this->repo->merchant_document->findDocumentsForMerchantIds([$merchantId])->pluck('id')->toArray());

        $entityIds = array_merge($entityIds, $this->repo->merchant_user->getAllUsersByMerchantId($merchantId)->pluck('id')->toArray());

        $entityIds = array_merge($entityIds, $this->repo->merchant_website->getAllWebsiteDetailsForMerchantId($merchantId)->pluck('id')->toArray());

        $entityIds = array_merge($entityIds, $this->repo->merchant_consents->getAllConsentDetailsForMerchant($merchantId)->pluck('id')->toArray());

        $entityIds = array_merge($entityIds, $this->repo->pricing->getPricingRuleIdsByMerchant($merchant));

        $entityIds = array_filter($entityIds);
        $entityIds = array_unique($entityIds);

        return $entityIds;
    }

    protected function getActorNameFromMeta(string $meta): string
    {
        $metaObj = json_decode($meta, true);
        $actorName = '';
        switch ($metaObj['actor_type'])
        {
            case 'admin':
                if (isset($this->adminActors[$metaObj['actor_id']]))
                {
                    $actorName = $this->adminActors[$metaObj['actor_id']];
                }
                else
                {
                    $actorName = $this->repo->admin->getAdminFromId($metaObj['actor_id'])->getName();
                    $this->adminActors[$metaObj['actor_id']] = $actorName;
                }
                break;

            case 'user':
                if (isset($this->userActors[$metaObj['actor_id']]))
                {
                    $actorName = $this->userActors[$metaObj['actor_id']];
                }
                else
                {
                    $actorName = $this->repo->user->getUserFromId($metaObj['actor_id'])->getName();
                    $this->userActors[$metaObj['actor_id']] = $actorName;
                }
                break;

            default:
                $actorName = "System";
                break;
        }

        if ($actorName != '')
        {
            // This is just to format an actor's username in this format - Name (Role) for better readability.
            // Eg: Mark (Admin)
            return ucwords($actorName).' ('.$metaObj['actor_type'].')';
        }

        return $actorName;
    }

    public function getAuditInfoV2(\RZP\Models\Merchant\Entity $merchant, int $startTime,int $endTime): array
    {

        $entityType = implode("' , '",self::MERCHANT_AUDIT_ENTITIES);
        $entityIds = $this->getEntityIdsForMerchant($merchant);

        $this->trace->info(
            TraceCode::AUDIT_INFO_ENTITY_LOG,
            [
                'entityType'      => $entityType,
                'entityIds'       => $entityIds
            ]
        );

        $entityIds = implode("' , '", $entityIds);

        $dataLakeQuery = sprintf(self::DATA_LAKE_AUDIT_QUERY,
                                 $entityIds,
                                 $entityType,
                                 $startTime,
                                 $endTime);

        $dataLakeResults =  $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);
        $results = [];

        foreach($dataLakeResults as $record)
        {
            $name = $this->getActorNameFromMeta($record['meta']);
            //unset($record['meta']);
            $record['user'] = $name;
            $results[] = $record;
        }

        return $results;
    }
}
