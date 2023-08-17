<?php

namespace RZP\Models\Payment\Downtime;

use Carbon\Carbon;
use Monolog\Logger;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Http\Request\Requests;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Constants\Entity as EntityConstants;

class Repository extends Base\Repository
{
    protected $entity = EntityConstants::PAYMENT_DOWNTIME;

    const KEY_OPERATOR_MAP = [
        Entity::ISSUER      => '=',
        Entity::METHOD      => '=',
        Entity::NETWORK     => '=',
        Entity::VPA_HANDLE  => '=',
    ];

    public function fetchOngoingDowntimes(): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        $query->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

        $query->whereNull(Entity::MERCHANT_ID);

        $this->excludeTurboDowntimesIfApplicable($query);

        return $query->get();
    }


    public function fetchScheduledDowntimes(): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(Entity::BEGIN, '>', Carbon::now()->getTimestamp())
              ->where(Entity::END, '>', Carbon::now()->getTimestamp())
              ->where(Entity::SCHEDULED, '=', true)
              ->whereNull(Entity::MERCHANT_ID);

        return $query->get();
    }

    public function fetchOngoingPlatformAndMerchantDowntimes(string $mid): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        $query->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

        $query->where(function ($query) use ($mid)
        {
            $query->where(Entity::MERCHANT_ID, '=', $mid)
                ->orWhereNull(Entity::MERCHANT_ID);
        });

        $this->excludeTurboDowntimesIfApplicable($query);

        return $query->get();
    }

    public function fetchOngoingDowntimesByMethodAndMerchant(string $method, string $mid=null): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(Entity::METHOD, $method);

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        $query->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

        if($mid != null)
        {
            $query->where(Entity::MERCHANT_ID, '=', $mid);
        }
        else
        {
            $query->whereNull(Entity::MERCHANT_ID);
        }

        return $query->get();
    }

    public function fetchResolvedDowntimes($params): PublicCollection
    {
        $query = $this->newQuery();
        $this->buildQuery(self::KEY_OPERATOR_MAP, $params, $query);

        $query->where(Entity::CREATED_AT, '>=', $this->dateToEpoch($params['startDate']));
        $query->where(Entity::CREATED_AT, '<=', $this->dateToEpoch($params['endDate']) + (Constants::SECONDS_IN_A_DAY - 1));
        $query->whereNull(Entity::MERCHANT_ID);
        $query->where(Entity::STATUS, '=', 'resolved');

        $query->orderBy(Entity::BEGIN, 'desc');
        return $query->get();
    }

    public function fetchOngoingDowntimesByMethodForMerchantsWithoutDowntimes(string $method, array $merchantsWithDowntime): PublicCollection
    {
        $query = $this->newQuery();

        $query->where(Entity::METHOD, $method);

        $query->where(function ($query) {
            $query->whereNull(Entity::END)
                ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        $query->where(Entity::BEGIN, '<=', Carbon::now()->getTimestamp());

        $query->whereNotNull(Entity::MERCHANT_ID);

        if (isset($merchantsWithDowntime) === true)
        {
            $query->whereNotIn(Entity::MERCHANT_ID, $merchantsWithDowntime);
        }

        return $query->get();
    }

    public function getDuplicate(array $input)
    {
        $query = $this->newQuery()
                      ->where(Entity::METHOD, $input[Entity::METHOD]);

        $query->where(function ($query) use ($input)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>=', $input[Entity::BEGIN]);
        });

        if (isset($input[Entity::END]) === true)
        {
            $query->where(Entity::BEGIN, '<=', $input[Entity::END]);
        }

        if (isset($input[Entity::MERCHANT_ID]) === true)
        {
            $query->where(Entity::MERCHANT_ID, '=', $input[Entity::MERCHANT_ID]);
        }
        else
        {
            $query->whereNull(Entity::MERCHANT_ID);
        }

        $this->addMethodSpecificQuery($query, $input);

        $this->trace->info(TraceCode::DUPLICATE_DOWNTIME_QUERY, ["query" => $query->toSql()]);

        return $query->first();
    }

    protected function addMethodSpecificQuery($query, $input)
    {
        $method = $input[Entity::METHOD];

        if (isset($input[Entity::TYPE]) and $input[Entity::TYPE] === Flow::IN_APP)
        {
            $attributes = Constants::getTurboQueryInstrument();
        }
        else
        {
            $attributes = Constants::getMethodQueryInstrument($method);
        }

        if (count($attributes) === 1)
        {
            $attribute = $attributes[0];

            $query->where($attribute, $input[$attribute]);
        }
        elseif (($input[Entity::TYPE] === Flow::IN_APP) and
                ($method === Method::UPI))
        {
            foreach ($attributes as $attribute)
            {
                if (isset($input[$attribute]) === true)
                {
                    $query->where($attribute, $input[$attribute]);
                }
            }
        }
        else
        {
            $query->where(function ($query) {
                $query->whereNull(Entity::TYPE)
                      ->orWhere(Entity::TYPE, '!=', Flow::IN_APP);
            });

            foreach ($attributes as $attribute)
            {
                if (isset($input[$attribute]) && !($input[$attribute] == Entity::NA || $input[$attribute] == Entity::UNKNOWN))
                {
                    $query->where($attribute, $input[$attribute]);
                    break;
                }
            }
        }
    }

    public function fetchFutureScheduledDowntimesToActivate(int $now): PublicCollection
    {
        $query = $this->newQuery()
                      ->where(Entity::BEGIN, '<=', $now)
                      ->where(Entity::STATUS, '=', Status::SCHEDULED);

        $query->where(function ($query)
        {
            $query->whereNull(Entity::END)
                  ->orWhere(Entity::END, '>', Carbon::now()->getTimestamp());
        });

        return $query->get();
    }

    public function fetchPastScheduledDowntimesToResolve(int $now): PublicCollection
    {
        $query = $this->newQuery()
                      ->where(function ($query) use ($now)
                      {
                        $query->whereNotNull(Entity::END)
                              ->where(Entity::END, '<=', $now);
                      });

        $query->whereIn(Entity::STATUS, [Status::SCHEDULED, Status::STARTED]);

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

    private function dateToEpoch($date)
    {
        return strtotime($date.' Asia/Kolkata');
    }

    /**
     * @param $query
     * This function modifies the query to exclude downtimes where type = in_app (turbo downtimes) if
     *  1. The current route is payments_downtime with method = GET
     *  2. The merchant making the request does not have in_app payment method enabled
     *
     * @return void
     */
    private function excludeTurboDowntimesIfApplicable(\RZP\Base\BuilderEx &$query)
    {
        if ($this->merchant !== null)
        {
            $routeName   = null;
            $routeMethod = null;

            try
            {
                /*
                 * If the code is running inside workers, it must be due to some flow apart from fetch multiple s2s call
                 * In such cases, we will simply return as we do not want to filter out turbo downtimes
                 */
                if ($this->app->runningInQueue() === true)
                {
                    return;
                }

                $routeName   = $this->route->getCurrentRouteName();
                $routeMethod = $this->route->getCurrentRouteMethod();
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::FAILED_TO_FETCH_ROUTE_NAME_OR_METHOD
                );
            }

            /*
             * This will be the case when the code is executed via SQS workers
             */
            if (($routeName === null) or ($routeMethod === null))
            {
                return;
            }

            $isInAppPaymentMethodEnabled = $this->repo->methods->getMethodsForMerchant($this->merchant)->isInAppEnabled();

            if (($routeName === Entity::PAYMENTS_DOWNTIME) and
                ($routeMethod === Requests::GET) and
                ($isInAppPaymentMethodEnabled !== true))
            {
                $query->where(function ($query)
                {
                    $query->whereNull(Entity::TYPE)
                          ->orWhere(Entity::TYPE, '!=', Flow::IN_APP);
                });
            }
        }
    }
}
