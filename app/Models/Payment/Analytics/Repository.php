<?php

namespace RZP\Models\Payment\Analytics;

use App;
use DB;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\Environment;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use Database\Connection;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\ConnectionType;
use RZP\Models\Payment\NewAnalytics;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\NewAnalytics\Transformer;

class Repository extends Base\Repository
{
    protected $entity = 'payment_analytics';

    protected $mode;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];

        parent::__construct();
    }

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_dash',
        Entity::CHECKOUT_ID     => 'sometimes|alpha_num',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    );

    protected $signedIds = [
        Entity::PAYMENT_ID,
    ];

    const PARTITION_NAME_PREFIX = 'p_payment_analytics_';

    const PARTITION_NAME = "partition_name";

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::PAYMENT_ID, 'desc');
    }

    public function fetch(array $params,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        // in prod, irrespective of connection in argument,
        // for payment analytics we should always fetch from tidb (admin)
        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $connectionType = ConnectionType::DATA_WAREHOUSE_ADMIN;
        }

        $entities = parent::fetch($params, $merchantId, $connectionType);

        return $entities;
    }

    // called for callback
    public function findForPayment($paymentId)
    {
        $timestamp = time() - Entity::SEARCH_WINDOW;

        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->get();
    }

    public function findLatestByPayment($paymentId)
    {
        $timestamp = time() - Entity::SEARCH_WINDOW;

        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - Payment\Entity::PAYMENT_WINDOW;

        return $this->newQuery()
                    ->where(Entity::CHECKOUT_ID, '=', $checkoutId)
                    ->where(Entity::CREATED_AT, '>=', $timestamp)
                    ->latest()
                    ->get();
    }

    /*
        There will be a total of 14 partitions for this table always, 6 of which will be for future dates.
        For e.g. if today is 8th May 2021 and the cron is yet to get triggered, 14 partitions will already be there as follows

        1) p_payment_analytics_01May2021 VALUES LESS THAN (1619913600)
        2) p_payment_analytics_02May2021 VALUES LESS THAN (1620000000)
        3) p_payment_analytics_03May2021 VALUES LESS THAN (1620086400)
        .
        .
        8) p_payment_analytics_08May2021 VALUES LESS THAN (1620518400) <<--we are here, cron is yet to run
        .
        .
        13) p_payment_analytics_13May2021 VALUES LESS THAN (1620950400)
        14) p_payment_analytics_Max VALUES LESS THAN (1619913600)

        When the cron runs, it will reorganize p_payment_analytics_Max into p_payment_analytics_14May2021 and p_payment_analytics_Max
        and the cron will drop the oldest partition i.e. p_payment_analytics_01May2021
    */

    public function createPartition()
    {
        $query = "ALTER TABLE " . $this->entity . " REORGANIZE PARTITION p_payment_analytics_Max into ( partition " . $this->getNewestPartitionName() . " values less than (" . $this->getNewestPartitionMaxCreatedAt() . "), partition p_payment_analytics_Max values less than MAXVALUE)";

        $this->trace->info(TraceCode::PAYMENT_ANALYTICS_PARTITION_CREATE_QUERY, ['query' => $query]);

        $connection = $this->getConnectionForPartitionQuery();

        DB::connection($connection)->statement($query);
    }

    public function dropPartition()
    {
        $query = "ALTER TABLE " . $this->entity . " drop partition " . $this->getOldestPartitionName();

        $this->trace->info(TraceCode::PAYMENT_ANALYTICS_PARTITION_DROP_QUERY, ['query' => $query]);

        $connection = $this->getConnectionForPartitionQuery();

        DB::connection($connection)->statement($query);
    }

    // partition to be created
    protected function getNewestPartitionName()
    {
        $partition = Carbon::now()->addDays(6)->format('dMY');

        return self::PARTITION_NAME_PREFIX . $partition;
    }

    protected function getNewestPartitionMaxCreatedAt()
    {
        return strval(Carbon::now()->addDays(7)->startOfDay()->timestamp);
    }

    // partition to be deleted
    protected function getOldestPartitionName()
    {
        $connection = $this->getConnectionForPartitionQuery();

        $db = DB::connection($connection)->getDatabaseName();

        $partitions = DB::select(DB::RAW("select partition_name as partition_name from information_schema.partitions where table_schema='$db' and table_name='$this->entity' and partition_ordinal_position=1"));

        // This will never happen though, we will always have one partition with ordinal position 1 as we are dropping only after creating new partition
        if (count($partitions) !== 1)
        {
            $this->trace->error(
                TraceCode::PAYMENT_ANALYTICS_PARTITION_ERROR,
                []);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'No partition to drop');
        }

        $this->trace->info(
            TraceCode::PAYMENT_ANALYTICS_PARTITON_TO_DROP,
            ['partition_being_dropped' => $partitions]);

        return $partitions[0]->partition_name;
    }

    protected function getConnectionForPartitionQuery()
    {
        $connection = ($this->mode === Mode::LIVE) ? Connection::PAYMENT_ANALYTICS_PARTITION_LIVE : Connection::PAYMENT_ANALYTICS_PARTITION_TEST;

        return $connection;
    }
}
