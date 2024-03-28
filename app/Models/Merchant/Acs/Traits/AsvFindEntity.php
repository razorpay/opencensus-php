<?php

namespace RZP\Models\Merchant\Acs\Traits;

use Redis;
use Cache;
use RZP\Constants\Entity as E;
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
            if ($this->isTransactionActive()) {
                $connectionType = Connection::ASV_WRITER;
            } else {
                $functionIdentifier = get_class($this) . " " . FunctionConstant::FIND;
                try {
                    return $this->getDetailsFromAsvIgnoreValidationAndNotFound($id, $oldConnection);
                } catch (\Exception $e) {
                    $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_EXCEPTION, [
                        "id" => $id,
                        "functionIdentifier" => $functionIdentifier,
                    ]);
                }
            }
        }
        return $this->findDatabase($id, $columns, $connectionType, $oldConnection);
    }
}

