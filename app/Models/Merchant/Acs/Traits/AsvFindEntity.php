<?php

namespace RZP\Models\Merchant\Acs\Traits;

use Redis;
use Cache;
use RZP\Constants\Entity as E;
use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\QueryCache\Constants;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\RepoToSdkWrapperMap;
use RZP\Exception;
use Database\Connection;


trait AsvFindEntity
{
    use AsvFind;

    public function find($id, $columns = array('*'), string $connectionType = null)
    {
        $oldConnection = $connectionType;
        $shouldCallAsv = $this->asvRouter->shouldRouteFindToAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND);
        if ($shouldCallAsv === true) {
            if ($connectionType != null || $columns != array("*") || !is_string($id) || $this->isTransactionActive()) {
                if ($connectionType == null) {
                    $connectionType = Connection::ASV_WRITER;
                } else {
                    if ($connectionType != Connection::ASV_WRITER) {
                        $this->trace->info(TraceCode::ACCOUNT_SERVICE_DO_NOT_ROUTE_REQUEST, [
                            "route_or_job_name" => $this->asvRouter->getRouteOrJobName(),
                            "connection_type" => $connectionType,
                            "columns" => $columns,
                            "id" => $id
                        ]);
                        $this->trace->count(Metric::ASV_REQUEST_NOT_ROUTED, [
                            'routeOrWorkerName' => $this->asvRouter->getRouteOrJobName(),
                            'reason' => $this->asvRouter::REQUEST_WITH_CONNECTION_TYPE,
                        ]);
                    }
                }
            } else {

                $functionIdentifier = get_class($this) . " " . FunctionConstant::FIND;
                try {
                    return $this->getDetailsFromAsvIgnoreValidationAndNotFound($id, $oldConnection);
                } catch (\Exception $e) {
                    $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_EXCEPTION, [
                        "id" => $id,
                        "functionIdentifier" => $functionIdentifier,
                    ]);
                    if($this->asvRouter->shouldFallbackToAsvDB(FunctionConstant::FIND)) {
                        $connectionType = Connection::ASV_WRITER;
                    }
                }
            }
        }

        return $this->findDatabase($id, $columns, $connectionType, $oldConnection);
    }
}

