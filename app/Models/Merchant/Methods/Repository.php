<?php

namespace RZP\Models\Merchant\Methods;

use App;
use RZP\Base\Common;
use RZP\Models\Base;
use RZP\Models\Base\QueryCache\CacheQueries;
use Illuminate\Support\Facades\Cache;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    use CacheQueries;

    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'methods';

    protected $appFetchParamRules = array(
        Entity::AMEX                   => 'sometimes|in:0,1',
        Entity::DISABLED_BANKS         => 'sometimes|in:0,1',
        Entity::CARD                   => 'sometimes|in:0,1',
        Entity::EMI                    => 'sometimes|numeric',
        Entity::MERCHANT_ID            => 'sometimes|alpha_num',
        Entity::MOBIKWIK               => 'sometimes|in:0,1',
        Entity::PAYTM                  => 'sometimes|in:0,1',
        Entity::PAYUMONEY              => 'sometimes|in:0,1',
        Entity::PAYZAPP                => 'sometimes|in:0,1',
        Entity::OLAMONEY               => 'sometimes|in:0,1',
        Entity::AIRTELMONEY            => 'sometimes|in:0,1',
        Entity::AMAZONPAY              => 'sometimes|in:0,1',
        Entity::FREECHARGE             => 'sometimes|in:0,1',
        Entity::CARD_NETWORKS          => 'sometimes|numeric',
        Entity::UPI_TYPE               => 'sometimes|numeric',
        Entity::DEBIT_EMI_PROVIDERS    => 'sometimes|numeric',
        Entity::CREDIT_EMI_PROVIDERS   => 'sometimes|numeric',
        Entity::CARDLESS_EMI_PROVIDERS => 'sometimes|numeric',
        Entity::PAYLATER_PROVIDERS     => 'sometimes|numeric',
        Entity::ITZCASH                => 'sometimes|in:0,1',
        Entity::OXIGEN                 => 'sometimes|in:0,1',
        Entity::AMEXEASYCLICK          => 'sometimes|in:0,1',
        Entity::PAYCASH                => 'sometimes|in:0,1',
        Entity::CITIBANKREWARDS        => 'sometimes|in:0,1',
    );

    public function fetchRouteName()
    {
        $app = App::getFacadeRoot();

        $ba = $app['basicauth'];

        $routeName =  $app['request.ctx']->getRoute();

        return $routeName;
    }

    public function getMethodsForMerchant(Merchant\Entity $merchant)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];

        $methods = $this->find($merchant->getId());

        if ($methods !== null)
        {
            $methods->merchant()->associate($merchant);

            $merchant->setRelation('methods', $methods);
        }

        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);

        return $methods;
    }

    public function isUpiEnabledForMerchant($merchantId)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $query = $this->newQuery()
                    ->select(Entity::UPI)
                    ->where(Entity::MERCHANT_ID, $merchantId);

        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        return $query->pluck(Entity::UPI)
            ->first();
    }

    public function fetchMethodsToUpdateHdfcDebitEmiValue($count)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $debitEmiProvider = $this->dbColumn(Entity::DEBIT_EMI_PROVIDERS);

        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        return $this->newQuery()
                    ->take($count)
                    ->whereNull($debitEmiProvider)
                    ->get();
    }

    public function fetchMethodsBasedOnMerchantIds($merchantIds)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID,$merchantIds)
            ->get();
    }

    public function fetchAllMethodsBasedOnMerchantIds($merchantIds)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID,$merchantIds)
            ->get();
    }

    public function fetchMethodsBasedOnMethodName($method,$from,$count)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        if($method == 'credit_emi')
        {
            $emiColumn = $this->dbColumn('emi');
            $emi = 1;
            $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
            return $this->newQuery()
                ->take($count)
                ->where(function ($query) use ($emiColumn,$emi)
                {
                    $query->where($emiColumn, '=', $emi)
                        ->orWhere($emiColumn, '=', $emi+2);
                })
                ->where(Common::CREATED_AT, '>', $from)
                ->orderBy(Common::CREATED_AT,'asc')
                ->get();
        }
        else if($method == 'debit_emi')
        {
            $emiColumn = $this->dbColumn('emi');
            $emi = 2;
            $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
            return $this->newQuery()
                ->take($count)
                ->where(function ($query) use ($emiColumn,$emi)
                {
                    $query->where($emiColumn, '=', $emi)
                        ->orWhere($emiColumn, '=', $emi+1);
                })
                ->where(Common::CREATED_AT, '>', $from)
                ->orderBy(Common::CREATED_AT,'asc')
                ->get();
        }
        else if($method == 'paylater' or $method == 'cardless_emi')
        {
            $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
            return $this->newQuery()
                ->take($count)
                ->where($method, '=', 1)
                ->where(Common::CREATED_AT, '>', $from)
                ->orderBy(Common::CREATED_AT,'asc')
                ->get();
        }


    }

    public function fetchBasedOnAffordabilityMethods($count,$paylater,$cardlessEmi,$emi,$from)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];

        $paylaterColumn = $this->dbColumn('paylater');
        $cardlessEmiColumn = $this->dbColumn('cardless_emi');
        $emiColumn = $this->dbColumn('emi');

        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        return $this->newQuery()
            ->take($count)
            ->where($paylaterColumn, '=', $paylater)
            ->where($cardlessEmiColumn, '=', $cardlessEmi)
            ->where(function ($query) use ($emiColumn,$emi)
            {
                $query->where($emiColumn, '=', $emi)
                    ->orWhere($emiColumn, '=', $emi+2);
            })
            ->where(Common::CREATED_AT, '>', $from)
            ->orderBy(Common::CREATED_AT,'asc')
            ->get();
    }

    public function bulkUpdateAddonMethodsForMerchants($merchantIds,$updatedMethods)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_UPDATE_METRIC, $metricData);
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->update([
                'addon_methods' => $updatedMethods,
            ]);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    protected function addQueryParamItzcash($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::ITZCASH,$params[Entity::ITZCASH]);
    }

    protected function addQueryParamOxigen($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::OXIGEN,$params[Entity::OXIGEN]);
    }

    protected function addQueryParamAmexeasyclick($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::AMEXEASYCLICK,$params[Entity::AMEXEASYCLICK]);
    }

    protected function addQueryParamPaycash($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::PAYCASH,$params[Entity::PAYCASH]);
    }

    protected function addQueryParamCitibankrewards($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::CITIBANKREWARDS,$params[Entity::CITIBANKREWARDS]);
    }

    protected function queryParamForAdditionalWallets(&$query,$wallet,$value)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $additional_wallets = $this->dbColumn(Entity::ADDITIONAL_WALLETS);
        if((bool)$value)
        {
            $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
            $query->where($additional_wallets,'like','%'.$wallet.'%');
        } else
        {
            $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
            $query->where($additional_wallets,'not like','%'.$wallet.'%');
        }
    }

    protected function addQueryParamInApp($query, $params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAddonMethods($query, Entity::UPI.'->'.Entity::IN_APP, $params[Entity::IN_APP]);
    }

    protected function addQueryParamSodexo($query, $params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAddonMethods($query, Entity::CARD.'->'.Entity::SODEXO, $params[Entity::SODEXO]);
    }

    protected function queryParamForAddonMethods(&$query, $method, $value)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $addonMethods = $this->dbColumn(Entity::ADDON_METHODS);
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $query->where($addonMethods . '->' . $method,'=', $value);
    }

    public function getMethodsForMerchantV2(Merchant\Entity $merchant)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->info(TraceCode::SET_METHODS_ON_EXPERIMENT,
            [
                'merchant' =>  'merchantV2'
            ]
        );

        $cacheKey = $this->entity . '_' . $merchant->getId();
        $methods = Cache::get($cacheKey);
        if ($methods === null)
        {
            $methods = $this->newQuery()
                ->where(Entity::MERCHANT_ID , '=', $merchant->getId())
                ->orderBy(COMMON::CREATED_AT,'desc')
                ->first();

            if ($methods!==null)
            {
                Cache::put($cacheKey, $methods->toJson(), $this->getCacheTtl());
            }
        }
        else{
            $methods=(new Merchant\Methods\Entity)->newFromBuilder($methods);
        }
        if ($methods !== null) {
            $methods->merchant()->associate($merchant);
            $merchant->setRelation('methods', $methods);
        }
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        return $methods;
    }

    public function methodsDualWrite($entity, array $options = array()) {
        $this->trace->info(TraceCode::METHODS_DUAL_WRITE, [
            'data' => $entity,
            'options' => $options,
        ]);

        //TODO: saveOrFail implementation is required for dualwrite flow and existing write flow for proxy call
        $this->saveOrFail($entity, $options);
    }

}
