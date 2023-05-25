<?php

namespace RZP\Models\Gateway\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Jobs\PaymentDowntime;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicCollection;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'gateway_downtime';
    private $SOURCES = ['statuscake','STATUSCAKE','vajra','VAJRA'];

    protected $entityFetchParamRules = array(
        Entity::GATEWAY     => 'sometimes|string|max:255',
        Entity::ISSUER      => 'sometimes|string|max:50',
        Entity::METHOD      => 'sometimes|string|max:30',
        Entity::BEGIN       => 'sometimes|integer',
        Entity::END         => 'required_with:begin|integer',
        Entity::PARTIAL     => 'sometimes|bool',
        Entity::SOURCE      => 'sometimes|string|max:30',
        Entity::VPA_HANDLE  => 'sometimes|string|max:255',
        Entity::MERCHANT_ID => 'sometimes|string|max:255',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY     => 'sometimes|string|max:255',
        Entity::ISSUER      => 'sometimes|string|max:50',
        Entity::ACQUIRER    => 'sometimes|string|max:30',
        Entity::METHOD      => 'sometimes|string|max:30',
        Entity::BEGIN       => 'sometimes|integer',
        Entity::END         => 'required_with:begin|integer',
        Entity::PARTIAL     => 'sometimes|bool',
        Entity::SOURCE      => 'sometimes|string|max:30',
        Entity::VPA_HANDLE  => 'sometimes|string|max:255',
        Entity::MERCHANT_ID => 'sometimes|string|max:255',
    );

    const KEY_OPERATOR_MAP = [
        Entity::GATEWAY     => '=',
        Entity::ISSUER      => '=',
        Entity::ACQUIRER    => '=',
        Entity::METHOD      => '=',
        Entity::SOURCE      => '=',
        Entity::TERMINAL_ID => '=',
        Entity::NETWORK     => '=',
        Entity::VPA_HANDLE  => '=',
        Entity::COMMENT     => '=',
        Entity::MERCHANT_ID => '=',
    ];

    const UNIQUE_KEYS = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::SOURCE,
        Entity::NETWORK,
        Entity::VPA_HANDLE,
        Entity::MERCHANT_ID,
        Entity::CARD_TYPE,
    ];

    public function saveOrFail($entity, array $options = [])
    {
        parent::saveOrFail($entity, $options);

        if (($this->isValidDowntimeSource($entity)) === true && $this->isPgAvailabilityGatewayDowntime($entity) === false ) {
            // Every update of gateway downtimes table should
            // queue a refresh of the payment downtimes table
            $paymentDowntimesEnabled = (bool)ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIMES, false);

            // Will enable on prod after PaymentDowntime logic is more thoroughly tested.
            if ($paymentDowntimesEnabled === true) {
                PaymentDowntime::dispatch($this->app['rzp.mode']);
            }

        }
       try
       {
            // we send gatewayDowntime Data to Smart Routing
            $this->app->smartRouting->sendDowntimesSmartRouting($entity);
        }
        catch (\Throwable $ex)
        {

            $this->trace->error(TraceCode::SMART_ROUTING_DOWNTIME_CACHE_WRITE_ERROR, [
                "gateway_downtime_data" => $entity,
                'error'    => $ex->getMessage(),
            ]);

        }

    }
    public function deleteOrFail($entity)
    {
        parent::deleteOrFail($entity);

        // Every delete of gateway downtimes table should
        // queue a refresh of the payment downtimes table
        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIMES, false);

        // Will enable on prod after PaymentDowntime logic is more thoroughly tested.
        if ($paymentDowntimesEnabled === true)
        {
            PaymentDowntime::dispatch($this->app['rzp.mode']);
        }

        try {
            // we delete gatewayDowntime Data from Smart Routing
            $this->app->smartRouting->deleteDowntimesSmartRouting($entity);
        }
        catch (\Throwable $ex){

            $this->trace->error(TraceCode::SMART_ROUTING_DOWNTIME_CACHE_WRITE_ERROR, [
                "gateway_downtime_data" => $entity,
                "error"    => $ex->getMessage(),
            ]);
        }
    }

    public function archive(Entity $entity)
    {
        $entity->archive();

        $this->syncToEs($entity, EsRepository::DELETE);
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function getConflictingDowntime($input, array $uniqueRecordIdentifiers = [])
    {
        $params = [];

        $uniqueKeys = $uniqueRecordIdentifiers ?: self::UNIQUE_KEYS;

        foreach ($uniqueKeys as $key)
        {
            if (isset($input[$key]) === true)
            {
                $params[$key] = $input[$key];
            }
        }

        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP, $params, $query);

        if (isset($params[Entity::TERMINAL_ID]) === false)
        {
            $query->whereNull(Entity::TERMINAL_ID);
        }

        if(isset($params[Entity::MERCHANT_ID]) === false)
        {
            $query->whereNull(Entity::MERCHANT_ID);
        }

        $this->addOverlapQuery($query, $input);

        return $query->orderBy(Entity::CREATED_AT)
                     ->first();
    }

    /**
     * This looks complicated, but it works.
     *
     * If you're not absolutely certain what
     * you're doing, don't fucking touch it.
     *
     * @param [type] $query [description]
     * @param [type] $input [description]
     */
    protected function addOverlapQuery($query, $input)
    {
        // We are not adding overlap query if `begin` is not set
        // Since `begin` is a required field this will not pass the validation check later in code
        if (isset($input[Entity::BEGIN]) === false)
        {
            return;
        }

        $query->where(function ($query) use ($input)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>=', $input[Entity::BEGIN]);
        });

        if (isset($input[Entity::END]) === true)
        {
            $query->where(Entity::BEGIN, '<=', $input[Entity::END]);
        }
    }

    public function fetchMostRecentActive(array $input, array $fetchByKeys = [])
    {
        $params = [];

        $uniqueKeys = empty($fetchByKeys) === true ? self::UNIQUE_KEYS : $fetchByKeys;

        foreach ($uniqueKeys as $key)
        {
            if (isset($input[$key]) === true)
            {
                $params[$key] = $input[$key];
            }
        }

        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP, $params, $query);

        if (isset($params[Entity::TERMINAL_ID]) === false)
        {
            $query->whereNull(Entity::TERMINAL_ID);
        }

        if(isset($params[Entity::MERCHANT_ID]) === false)
        {
            $query->whereNull(Entity::MERCHANT_ID);
        }

        return $query->whereNull(Entity::END)
                     ->where(Entity::SCHEDULED, '=', false)
                     ->latest()
                     ->first();
    }

    /**
     * Fetch all current and future down times. This means any down time which has a future end time or null end time
     * qualify for this case
     *
     * @return PublicCollection
     *
     */

    public function fetchCurrentAndFutureDowntimes(bool $withoutTerminal = false, bool $refreshRouterCache = false): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        if ($withoutTerminal === true)
        {
            $query->whereNull(Entity::TERMINAL_ID);
        }

        if ($refreshRouterCache === true)
        {

            $query->where(Entity::GATEWAY,'!=',Entity::ALL);

            $query->whereIn(Entity::METHOD , [Payment\Method::CARD,Payment\Method::UPI]);
        }

        return $query->get();
    }

    public function fetchPastDowntimes(): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(Entity::END, '<', Carbon::now()->getTimestamp());

        return $query->limit(1000)->get();
    }

    public function fetchDowntimesWithoutTerminal(array $input, array $methods): PublicCollection
    {
        $query = $this->newQuery();

        $this->buildFetchQuery($query, $input);

        if (empty($methods) === false)
        {
            $query->whereIn(Entity::METHOD, $methods);
        }

        return $query->whereNull(Entity::TERMINAL_ID)
                     ->get();
    }

    /**
     * Fetches downtimes for DonwtimeSorter
     *
     * Params are provided as [key => val]
     * where 'val' can either be an array or string
     *
     * Raw Sql :
     * "select * from `gateway_downtimes` where
     *  `gateway` in (?, ?, ?) and
     *  `partial` = ? and
     *  `begin` <= ? and
     *  (`end` is null or `end` >= ?) and
     *  `method` in (?, ?) and
     *  `network` in (?, ?) and
     *  `card_type` in (?, ?) and
     *  `issuer` in (?, ?)"
     *
     * @param $params array
     * @return collection
     */
    public function fetchApplicableDowntimesForPayment(array $params) : Base\PublicCollection
    {
        $query = $this->newQuery();

        foreach ($params as $key => $value)
        {
            // using this so we can add `end` & `begin` params
            // to query using addQueryParamEnd / addQueryParamBegin
            $func = 'addQueryParam' . studly_case($key);

            if (method_exists($this, $func))
            {
                $this->$func($query, $params);
            }
            // case when the comparison has to be on an array of values
            else if (is_array($value) === true)
            {
                $query->whereIn($key, $value);
            }
            // simple '=' comparator
            else
            {
                $query->where($key, '=', $value);
            }
        }

        $query = $query->where(Entity::GATEWAY, '!=', Entity::ALL);

        return $query->get();
    }

    protected function buildQuery(
        array $keyOperatorMap, array $input, \RZP\Base\BuilderEx & $query)
    {
        foreach ($keyOperatorMap as $key => $operator)
        {
            if (isset($input[$key]) === true)
            {
                $query->where($key, $operator , $input[$key]);
            }
        }
    }

    protected function addQueryParamBegin($query, $params)
    {
        if (empty($params[Entity::END]) === false)
        {
            return;
        }

        // The default value for Entity::END is null. This is because we do not
        // necessarily know the end time in case of an unscheduled downtime.
        //
        // If an end time does exist, downtime should have ended after
        // the start of the query begin time for there to be an overlap

        $query->where(Entity::BEGIN, '<=', $params[Entity::BEGIN]);

        $query->where(function ($query) use ($params)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>=', $params[Entity::BEGIN]);
        });
    }

    protected function addQueryParamEnd($query, $params)
    {
        $query->where(function ($query) use ($params)
        {
            $query->orWhere(function ($query) use ($params)
            {
                $query->where(Entity::BEGIN, '<=', $params[Entity::BEGIN]);

                $query->where(function ($query) use ($params)
                {
                    $query->whereNull(Entity::END)
                          ->orWhere(Entity::END, '>=', $params[Entity::BEGIN]);
                });
            });

            // If query does have an endtime, then either downtime should
            // have begun before it for there to be an overlap
            $query->orWhere(function ($query) use ($params)
            {
                $query->where(Entity::BEGIN, '<=', $params[Entity::END]);

                $query->where(function ($query) use ($params)
                {
                    $query->whereNull(Entity::END)
                          ->orWhere(Entity::END, '>=', $params[Entity::END]);
                });

            });

            // or there can be downtimes which started after begin but ended before
            // the end time
            $query->orWhere(function ($query) use ($params)
            {
                $query->where(Entity::BEGIN, '>=', $params[Entity::BEGIN])
                      ->whereNotNull(Entity::END)
                      ->where(Entity::END, '<=', $params[Entity::END]);
            });
        });
    }

    public function fetchActiveDowntime($params)
    {
        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP,  $params, $query);

        return $query->whereNull(Entity::END)
            ->get();
    }

    public function fetchResolvedDowntime($params)
    {
        $query = $this->newQuery();

        $this->buildQuery(self::KEY_OPERATOR_MAP,  $params, $query);

        return $query->whereNotNull(Entity::END)
            ->where(Entity::BEGIN, '>=', $params[Entity::BEGIN])
            ->get();
    }

    /**
     * @param $entity
     * @return bool
     */
    protected function isValidDowntimeSource($entity): bool
    {
        //if the source is statuscake or vajra it is considered to be an invalid source
        // and a refresh of payment downtimes table isn't required for downtimes with such source
        if ((empty($entity['source']) === false) and (isset($entity['source']) === true) and (in_array($entity['source'], $this->SOURCES) === true)) {

            return false;
        }

        return true;
    }

    protected function isPgAvailabilityGatewayDowntime($entity): bool
    {
        if((isset($entity[Entity::SOURCE]) === true) && ($entity[Entity::SOURCE] == Source::DOWNTIME_SERVICE)){
            if(isset($entity[Entity::GATEWAY])===true && $entity[Entity::GATEWAY] != Entity::ALL ){
                return true;
            }

        }

        return false;
    }

}
