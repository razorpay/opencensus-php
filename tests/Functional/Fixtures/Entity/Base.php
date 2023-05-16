<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Config;
use Eloquent;
use RZP\Models;
use RZP\Constants\Entity as E;
use RZP\Tests\TestDummy\Factory;
use Illuminate\Support\Facades\DB;
use RZP\Tests\Functional\Fixtures\Fixtures;

class Base
{
    public static $fixturesInstance;

    public function __construct()
    {
        $this->fixtures = self::$fixturesInstance;

        $this->db = DB::getFacadeRoot();
    }

    protected static $map = [
        'key'                   => \RZP\Models\Key\Entity::class,
        'iin'                   => \RZP\Models\Card\IIN\Entity::class,
        'card'                  => \RZP\Models\Card\Entity::class,
        'item'                  => \RZP\Models\Item\Entity::class,
        'user'                  => \RZP\Models\User\Entity::class,
        'batch'                 => \RZP\Models\Batch\Entity::class,
        'order'                 => \RZP\Models\Order\Entity::class,
        'order_outbox'          => \RZP\Models\OrderOutbox\Entity::class,
        'order_meta'            => \RZP\Models\Order\OrderMeta\Entity::class,
        'token'                 => \RZP\Models\Customer\Token\Entity::class,
        'device'                => \RZP\Models\Device\Entity::class,
        'payout'                => \RZP\Models\Payout\Entity::class,
        'addon'                 => \RZP\Models\Plan\Subscription\Addon\Entity::class,
        'refund'                => \RZP\Models\Payment\Refund\Entity::class,
        'address'               => \RZP\Models\Address\Entity::class,
        'balance'               => \RZP\Models\Merchant\Balance\Entity::class,
        'credits'               => \RZP\Models\Merchant\Credits\Entity::class,
        'feature'               => \RZP\Models\Feature\Entity::class,
        'invoice'               => \RZP\Models\Invoice\Entity::class,
        'methods'               => \RZP\Models\Merchant\Methods\Entity::class,
        'webhook'               => \RZP\Models\Merchant\Webhook\Entity::class,
        'payment'               => \RZP\Models\Payment\Entity::class,
        'pricing'               => \RZP\Models\Pricing\Entity::class,
        'dispute'               => \RZP\Models\Dispute\Entity::class,
        'customer'              => \RZP\Models\Customer\Entity::class,
        'merchant'              => \RZP\Models\Merchant\Entity::class,
        'merchant_access_map'   => \RZP\Models\Merchant\AccessMap\Entity::class,
        'partner_config'        => \RZP\Models\Partner\Config\Entity::class,
        'merchant_application'  => \RZP\Models\Merchant\MerchantApplications\Entity::class,
        'merchant_user'         => \RZP\Models\Merchant\MerchantUser\Entity::class,
        'terminal'              => \RZP\Models\Terminal\Entity::class,
        'transfer'              => \RZP\Models\Transfer\Entity::class,
        'schedule'              => \RZP\Models\Schedule\Entity::class,
        'emi_plan'              => \RZP\Models\Emi\Entity::class,
        'app_token'             => \RZP\Models\Customer\AppToken\Entity::class,
        'line_item'             => \RZP\Models\LineItem\Entity::class,
        'adjustment'            => \RZP\Models\Adjustment\Entity::class,
        'credit_transfer'       => \RZP\Models\CreditTransfer\Entity::class,
        'settlement'            => \RZP\Models\Settlement\Entity::class,
        'settlement_transfer'   => \RZP\Models\Settlement\Transfer\Entity::class,
        'transaction'           => \RZP\Models\Transaction\Entity::class,
        'bank_account'          => \RZP\Models\BankAccount\Entity::class,
        'fee_breakup'           => \RZP\Models\Transaction\FeeBreakup\Entity::class,
        'schedule_task'         => \RZP\Models\Schedule\Task\Entity::class,
        'gateway_token'         => \RZP\Models\Customer\GatewayToken\Entity::class,
        'risk'                  => \RZP\Models\Risk\Entity::class,
        'payment_analytics'     => \RZP\Models\Payment\Analytics\Entity::class,
        'd2c_bureau_detail'     => \RZP\Models\D2cBureauDetail\Entity::class,
        'offline_device'        => \RZP\Models\Offline\Device\Entity::class,
        'atom'                  => \RZP\Gateway\Atom\Entity::class,
        'hdfc'                  => \RZP\Gateway\Hdfc\Entity::class,
        'enach'                 => \RZP\Gateway\Enach\Base\Entity::class,
        'netbanking'            => \RZP\Gateway\Netbanking\Base\Entity::class,
        'wallet'                => \RZP\Gateway\Wallet\Base\Entity::class,
        'axis_migs'             => \RZP\Gateway\AxisMigs\Entity::class,
        'billdesk'              => \RZP\Gateway\Billdesk\Entity::class,
        'cardless_emi'          => \RZP\Gateway\CardlessEmi\Entity::class,
        'options'               => \RZP\Models\Options\Entity::class,
        'config'                => \RZP\Models\Payment\Config\Entity::class,
        'counter'               => \RZP\Models\Counter\Entity::class,
        'payout_source'         => \RZP\Models\PayoutSource\Entity::class,
        'payouts_details'       => \RZP\Models\PayoutsDetails\Entity::class,
        'banking_account_tpv'   => \RZP\Models\BankingAccountTpv\Entity::class,
        'sub_virtual_account'   => \RZP\Models\SubVirtualAccount\Entity::class,
        'payment_fraud'         => \RZP\Models\Payment\Fraud\Entity::class,

        'settlement.ondemand_fund_account' => \RZP\Models\Settlement\OndemandFundAccount\Entity::class,
        'settlemnt.ondemand'               => \RZP\Models\Settlement\Ondemand\Entity::class,
        'settlement.ondemand_payout'       => \RZP\Models\Settlement\OndemandPayout\Entity::class,
        'merchant_notification_config'     => \RZP\Models\Merchant\MerchantNotificationConfig\Entity::class,
        'x_app'                            => \RZP\Models\AppFramework\App\Entity::class,
        'reward'                           => \RZP\Models\Reward\Entity::class,
        'merchant_reward'                  => \RZP\Models\Reward\MerchantReward\Entity::class,

        'survey'                           => \RZP\Models\Survey\Entity::class,
        'survey_tracker'                   => \RZP\Models\Survey\Tracker\Entity::class,
        'survey_response'                  => \RZP\Models\Survey\Response\Entity::class,

        'application'                      => \RZP\Models\Application\Entity::class,
        'application_mapping'              => \RZP\Models\Application\ApplicationTags\Entity::class,

        'wallet_account'                   => \RZP\Models\WalletAccount\Entity::class,
        'payouts_status_details'           => \RZP\Models\PayoutsStatusDetails\Entity::class,
        'bank_transfer'                     => \RZP\Models\BankTransfer\Entity::class,
        'role_access_policy_map'            => \RZP\Models\RoleAccessPolicyMap\Entity::class,
    ];

    protected static $liveAndTest = [
        'merchant',
        'pricing',
        'methods',
        'emi_plan',
        'iin',
        'schedule',
        'feature',
        'user',
        'merchant_application',
        'merchant_access_map',
        'partner_config',
        'merchant_user'
    ];

    public function create(array $attributes = [])
    {
        $entity = snake_case(explode('\\', get_class($this))[5]);

        return $this->createEntity($entity, $attributes);
    }

    public function createEntity($entity, array $attributes = array())
    {
        if (E::isEntitySyncedInLiveAndTest($entity))
        {
            return $this->createEntityInTestAndLive($entity, $attributes);
        }

        return $this->save($entity, $attributes);
    }

    public function edit($id, array $attributes = array())
    {
        $entity = snake_case(explode('\\', get_class($this))[5]);

        return $this->editEntity($entity, $id, $attributes);
    }

    public function editEntity($entity, $id, array $attributes = array())
    {
        $this->fixtures->stripSign($id);

        if (E::isEntitySyncedInLiveAndTest($entity))
        {
            return $this->editEntityInTestAndLive($entity, $id, $attributes);
        }

        $entity = E::getEntityClass($entity);
        $entity = $entity::findOrFail($id);

        foreach ($attributes as $key => $value)
        {
            $entity[$key] = $value;
        }

        $entity->saveOrFail();

        return $entity;
    }

    public function createEntityInTestAndLive($entity, $attributes = [])
    {
        $this->eloquentUnguard();

        $entity = E::getEntityClass($entity);

        $entity = Factory::build($entity, $attributes);

        $testEntity = unserialize(serialize($entity));
        $liveEntity = unserialize(serialize($entity));

        $testEntity->setConnection('test')->saveOrFail();
        $liveEntity->setConnection('live')->saveOrFail();

        $entity->exists = true;
        $entity->setRawAttributes($liveEntity->getAttributes(), true);

        $this->eloquentReguard();

        $this->fixtures->setDefaultConn();

        return $entity;
    }

    public function editEntityInTestAndLive($entity, $id, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = E::getEntityClass($entity);
        $entity = $entity::findOrFail($id);

        foreach ($attributes as $key => $value)
        {
            $entity[$key] = $value;
        }

        $testEntity = clone $entity;
        $liveEntity = clone $entity;

        $testEntity->setConnection('test')->saveOrFail();
        $liveEntity->setConnection('live')->saveOrFail();

        $entity->setRawAttributes($liveEntity->getAttributes(), true);

        $this->eloquentReguard();

        $this->fixtures->setDefaultConn();

        return $entity;
    }

    public function build($entity, $attributes)
    {
        $this->eloquentUnguard();

        $entity = E::getEntityClass($entity);

        $entity = Factory::build($entity, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function save($entity, $attributes)
    {
        $this->eloquentUnguard();

        $entityClass = E::getEntityClass($entity);

        $entity = Factory::create($entityClass, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function transaction(callable $callable)
    {
        $db = \DB::getFacadeRoot();

        return $db->transaction($callable);
    }

    protected function callInTransaction($callable, $args)
    {
        return $this->db->transaction(function () use ($callable)
        {
            return call_user_func($callable);
        });
    }

    protected function eloquentUnguard()
    {
        Eloquent::unguard();

        return $this;
    }

    protected function eloquentReguard()
    {
        Eloquent::reguard();

        return $this;
    }

    public function connection($mode = 'test')
    {
        Config::set('database.default', $mode);

        return $this;
    }

    public function on($mode)
    {
        $this->connection($mode);

        return $this;
    }

    public function onLive()
    {
        $this->on('live');

        return $this;
    }

    public function onTest()
    {
        $this->on('test');

        return $this;
    }

    protected function getNamespace()
    {
        return substr(get_called_class(), 0, strrpos(get_called_class(), '\\'));
    }
}
