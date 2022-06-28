<?php

namespace RZP\Models\Base\Audit;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;

class Core extends Base\Core
{
    const DATA_LAKE_ENTITY_AUDIT_QUERY  = "select audit.changelog,info.meta,audit.timestamp as modified_at,audit.created_date from hive.realtime_entity_meta.audit audit inner join hive.realtime_hudi_api.audit_info info on info.id = audit.audit_id where audit.service='api' and audit.entity_type = '%s' and audit.producer_created_date<='%s' and audit.entity_id='%s' and audit.timestamp < %s limit %s";

    public function create()
    {
        $auditInfo = new Entity;

        $auditInfo->generateId();

        $facadeRoot = App::getFacadeRoot();

        $ba = $facadeRoot['basicauth'];

        $request = $facadeRoot['request'];

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
}
