<?php

namespace RZP\Models\Payment\Analytics;

use App;
use DB;
use RZP\Constants\Environment;
use RZP\Models\Base;
use RZP\Constants\Partitions;
use RZP\Models\Payment;
use RZP\Base\ConnectionType;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\Traits\PartitionRepo;
use Rzp\Wda_php\SortOrder;

class Repository extends Base\Repository
{
    use PartitionRepo;

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

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::PAYMENT_ID, 'desc');
    }

    protected function addWDAQueryOrder($wdaQueryBuilder)
    {
        $wdaQueryBuilder->sort($this->getTableName(), Entity::PAYMENT_ID, SortOrder::DESC);
    }

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
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

    public function findByPaymentID($paymentId)
    {
        $timestamp = time() - (5 * Entity::SEARCH_WINDOW);

        return $this->newQuery()
            ->where(Entity::PAYMENT_ID, '=', $paymentId)
            ->where(Entity::CREATED_AT, '>=', $timestamp)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }
}
