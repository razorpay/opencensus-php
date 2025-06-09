<?php

namespace RZP\Models\Merchant\Methods;

use App;
use RZP\Base\Common;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Base\QueryCache\CacheQueries;
use Illuminate\Support\Facades\Cache;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Repository extends Base\Repository
{
    use CacheQueries;

    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

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
        Entity::GRABPAY                => 'sometimes|in:0,1',
        Entity::ALIPAY                 => 'sometimes|in:0,1',
        Entity::GOPAY                  => 'sometimes|in:0,1',
        Entity::DOKU                   => 'sometimes|in:0,1',
        Entity::LINKAJA                => 'sometimes|in:0,1',
        Entity::OVO                    => 'sometimes|in:0,1',
        Entity::KLARNA                 => 'sometimes|in:0,1',
        Entity::ZIP                    => 'sometimes|in:0,1',
    );

    protected PaymentMethodsService $paymentMethodsService;
    public function __construct()
    {
        parent::__construct();

        $this->paymentMethodsService = $this->app['payment_methods_service'];
        $this->trace = $this->app['trace'];
    }

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
            'route'    => $this->fetchRouteName(),
            'function' => __FUNCTION__,
        ];

        $merchantId = $merchant->getId();

        // 1. Fetch methods from the database (current behavior)
        $dbMethods = $this->find($merchantId);

        $methods = $this->getMethods($merchantId, $dbMethods);

        // 4. Associate merchant and set relation if methods found
        if ($methods !== null)
        {
            $methods->merchant()->associate($merchant);
            $merchant->setRelation('methods', $methods);
        }

        // 5. Trace metric
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);

        return $methods;
    }

    public function isUpiEnabledForMerchant($merchantId)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);

        if ($this->paymentMethodsService->isMethodServiceReadEnabled())
        {
            try
            {
                $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);
                $serviceMethods = $this->paymentMethodsService->fetchMethodsFromService($merchantId, $path);

                if ($serviceMethods !== null)
                {
                    $upiStatus = $serviceMethods->getAttribute(Entity::UPI);
                    $this->trace->info(TraceCode::PAYMENT_METHODS_UPI_STATUS_FROM_SERVICE, [
                        'merchant_id' => $merchantId,
                        'upi_status'  => $upiStatus,
                    ]);

                    $this->trace->count(Methods\Metric::PAYMENT_METHOD_SERVICE_CALL_SUCCESS_METRIC);
                    return $upiStatus;
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->count(Methods\Metric::PAYMENT_METHOD_SERVICE_CALL_FAILED_METRIC);
                $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_METHODS_SERVICE_CALL_FAILED_FOR_UPI, [
                    'merchant_id' => $merchantId,
                    'reason'      => 'Service call failed for UPI status, falling back to DB.',
                ]);
            }
        }

        $query = $this->newQuery()
                    ->select(Entity::UPI)
                    ->where(Entity::MERCHANT_ID, $merchantId);

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

    /**
     * @param mixed $merchantId
     * @param $dbMethods
     * @return Entity|null
     */
    public function getMethods(mixed $merchantId, $dbMethods): ?Entity
    {
        $methods = null;
        if ($this->paymentMethodsService->isMethodServiceReadEnabled()) {
            $serviceMethods = null;

            try {
                // 2. Fetch methods from the payment_methods service
                $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);
                $serviceMethods = $this->paymentMethodsService->fetchMethodsFromService($merchantId, $path);

                // 3. Compare and decide
                if ($dbMethods !== null) {
                    // Compare only if DB methods exist
                    $areDifferent = $this->paymentMethodsService->areMethodsDifferent($serviceMethods, $dbMethods);
                    $this->trace->count(Methods\Metric::PAYMENT_METHOD_DIFF_COUNT, ['diff' => $areDifferent]);

                    if ($areDifferent === false) {
                        // No difference, prefer service methods
                        $methods = $serviceMethods;
                        $this->trace->info(TraceCode::PAYMENT_METHODS_USING_SERVICE_DATA, [
                            'merchant_id' => $merchantId,
                            'reason' => 'No difference found between DB and Service.'
                        ]);
                    } else {
                        // Difference found, use DB methods (current source of truth)
                        $methods = $dbMethods;
                        if ($serviceMethods != null) {
                            $this->trace->info(TraceCode::PAYMENT_METHODS_DIFF_FOUND, [
                                'merchant_id' => $merchantId,
                                'reason' => 'Difference found between DB and Service. Using DB data.',
                            ]);
                        }
                    }
                } else {
                    // DB methods don't exist, use service methods if fetched
                    $methods = $serviceMethods;
                    if ($methods !== null) {
                        $this->trace->info(TraceCode::PAYMENT_METHODS_USING_SERVICE_DATA, [
                            'merchant_id' => $merchantId,
                            'reason' => 'DB methods not found, using Service data.'
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Service call failed or comparison error, fallback to DB methods
                $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_METHODS_SERVICE_CALL_FAILED, [
                    'merchant_id' => $merchantId,
                    'fallback' => 'Using DB methods data due to service error.'
                ]);

                $methods = $dbMethods; // Fallback to DB data
            }
        } else {
            $this->trace->info(TraceCode::PAYMENT_METHODS_USING_API_DB_DATA, [
                'merchant_id' => $merchantId,
                'reason' => 'Splitz Experiment is off.',
            ]);
            $methods = $dbMethods;
        }
        return $methods;
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

    protected function addQueryParamAlipay($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::ALIPAY,$params[Entity::ALIPAY]);
    }

    protected function addQueryParamGopay($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::GOPAY,$params[Entity::GOPAY]);
    }

    protected function addQueryParamDoku($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::DOKU,$params[Entity::DOKU]);
    }

    protected function addQueryParamOvo($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::OVO,$params[Entity::OVO]);
    }

    protected function addQueryParamLinkaja($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAdditionalWallets($query,Entity::LINKAJA,$params[Entity::LINKAJA]);
    }

    protected function addQueryParamKlarna($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAddonMethods($query,Entity::KLARNA,$params[Entity::KLARNA]);
    }

    protected function addQueryParamZip($query,$params)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_READ_METRIC, $metricData);
        $this->queryParamForAddonMethods($query,Entity::ZIP,$params[Entity::LINKAJA]);
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

            $methods = $this->getMethods($merchant->getId(), $methods);

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

        $this->saveOrFail($entity, $options);
    }

    public function saveOrFail($entity, array $options = array())
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            'function' => __FUNCTION__
        ];
        $this->trace->count(Methods\Metric::PAYMENT_METHODS_UPDATE_METRIC, $metricData);

        if ($this->paymentMethodsService->isMethodServiceWriteEnabled()){
            try{
                $this->paymentMethodsService->saveMethods($entity, $options);
                return;
            }catch(\Throwable $exception)
            {
                $this->trace->traceException($exception, Trace::ERROR, TraceCode::PAYMENT_METHODS_SERVICE_UPDATE_CALL_FAILED, [
                    'merchant_id' => $entity->getMerchantId(),
                    'exception_message' => $exception->getMessage(),
                ]);
            }
        }

        try
        {
            $this->trace->info(TraceCode::PAYMENT_METHODS_DB_UPDATE_FLOW, ["method_entity" => $entity]);
            $this->saveOrFailTestAndLive($entity, $options);
        }
        catch (\Throwable $exception)
        {
            $this->trace->Error(TraceCode::UPDATE_MERCHANT_METHODS_FAILED, ['exception' =>  $exception]);
            $msg = $exception->getMessage();
            if (str_contains($msg, "A row in test and live database do not match"))
            {
                $this->trace->count(Methods\Metric::PAYMENT_METHOD_DIFF_METRIC, $metricData);
            }
            $this->trace->count(Methods\Metric::PAYMENT_METHOD_UPDATE_FAILED_METRIC, $metricData);

            throw $exception;
        }

    }
}
