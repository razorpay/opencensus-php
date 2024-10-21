<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Base;
use RZP\Models\Merchant;
use Database\Connection;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Merchant as AsvSdkMerchantQuery;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::ID                 => 'sometimes|string|min:14',
        Entity::EMAIL              => 'sometimes|email',
        Entity::PARENT_ID          => 'sometimes|string|size:14',
        EsRepository::SEARCH_HITS  => 'filled|boolean',
        EsRepository::QUERY        => 'filled|string|min:2|max:100',
        Entity::ACCOUNT_CODE       => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
    ];

    protected function addQueryParamId($query, $params)
    {
        $id = $params[Entity::ID];

        Entity::stripSignWithoutValidation($id);

        $query->where(Entity::ID, '=', $id);
    }

    public function findByIdAndMerchant(string $id,
                                        Merchant\Entity $merchant,
                                        array $params = [],
                                        string $connectionType = null): PublicEntity {

        if ($this->asvRouter->shouldRouteFilterToAsv('accountFindByIdAndMerchant')) {
            return $this->findByAccountIdAndParent($id, $merchant, false, false);
        }

        return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    public function fetchFromAsv(array $params, string $merchantId = null, string $connectionType = null)
    {
        if (!$this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__)) {
            return $this->fetch($params, $merchantId, $connectionType);
        }

        if(isset($params['count']) === false)
        {
            $params['count'] = 10;
        }

        list($mysqlParams) = $this->getMysqlAndEsParams($params);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(Connection::ASV_WRITER));
        $this->addCommonQueryParamMerchantId($query, $merchantId);
        $dbQuery = $this->buildFetchQuery($query, $mysqlParams);

        if ($this->isTransactionActive())
        {
            $results = $dbQuery->get();
        }
        else {
            try {
                $results = (new AsvSdkMerchantQuery())->fetchFromAccountService($dbQuery->toSql(), $dbQuery->getBindings());
            } catch (\Exception $e) {
                $this->trace->error(
                    TraceCode::ACCOUNT_SERVICE_FETCH_EXCEPTION,
                    ["error" => $e->getMessage()]
                );
                $results = $dbQuery->get();
            }
        }
        $this->resetConnectionOnModels($results);
        return $results;
    }
}
