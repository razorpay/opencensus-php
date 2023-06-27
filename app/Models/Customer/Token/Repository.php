<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;
use DB;

use Illuminate\Database\Eloquent\Builder;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use Rzp\Wda_php\Cluster;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Base\ConnectionType;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Method;
use RZP\Exception\ServerErrorException;
use Rzp\Wda_php\WDARegisterQueryRequestBuilder;

class Repository extends Base\Repository
{
    protected $entity = 'token';

    protected $appFetchParamRules = [
        Entity::METHOD              => 'sometimes|alpha',
        Entity::CUSTOMER_ID         => 'sometimes|alpha_num',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::TERMINAL_ID         => 'sometimes|alpha_num',
        Entity::TOKEN               => 'sometimes|alpha_num',
        Entity::CARD_ID             => 'sometimes|alpha_num',
        Entity::BANK                => 'sometimes|alpha',
        Entity::WALLET              => 'sometimes|alpha',
        Entity::RECURRING           => 'sometimes|in:0,1',
        Entity::RECURRING_STATUS    => 'sometimes|alpha|max:20',
    ];

    public function getByCustomer($customer, bool $withVpas = false, $merchantId = null, $mode = 'test')
    {
        $isPassUnusedRejectedTokensExperimentEnabled = '';

        if ($merchantId !== null)
        {
            $isPassUnusedRejectedTokensExperimentEnabled = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::PASS_REJECTED_UNUSED_TOKENS,
                $mode
            );
        }

        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(function($query) use ($isPassUnusedRejectedTokensExperimentEnabled)
                    {
                        if (strtolower($isPassUnusedRejectedTokensExperimentEnabled) === 'on')
                        {
                            $query->whereNull(Token\Entity::USED_AT)
                                  ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::REJECTED);
                        }
                        $query->orWhereNotNull(Token\Entity::USED_AT);
                    })
                    ->where(function($query)
                    {
                        $query->whereNull(Token\Entity::EXPIRED_AT)
                              ->orWhere(Token\Entity::EXPIRED_AT, '>', time());
                    })
                    ->withVpaTokens($withVpas)
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->orderBy(Token\Entity::ID, 'desc')
                    ->get();
    }

    public function getGlobalOrLocalTokenEntityOfPayment($payment)
    {
        $token = null;

        if ($payment->getTokenId() !== null)
        {
            $token = $this->findOrFail($payment->getTokenId());
            $payment->localToken()->associate($token);
        }
        else if ($payment->getGlobalTokenId() !== null)
        {
            $token = $this->findOrFail($payment->getGlobalTokenId());
            $payment->globalToken()->associate($token);
        }

        return $token;
    }

    public function getByTokenAndCustomer($token, Customer\Entity $customer)
    {
        $token = $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Token\Entity::TOKEN, '=', $token)
                    ->first();

        if ($token !== null)
        {
            $token->customer()->associate($customer);
        }

        return $token;
    }

    public function getByTokenAndMerchant($token, Merchant\Entity $merchant)
    {
        $token = $this->newQuery()
            ->where(Token\Entity::MERCHANT_ID, '=', $merchant->getId())
            ->where(Token\Entity::TOKEN, '=', $token)
            ->first();

        if ($token !== null)
        {
            $token->merchant()->associate($merchant);
        }

        return $token;
    }

    public function getByToken(string $tokenId)
    {
        $token = $this->newQuery()
            ->where(Token\Entity::TOKEN, '=', $tokenId)
            ->first();

        return $token;
    }

    public function getByTokenAndCustomerId(string $token, string $customerId)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customerId)
                    ->where(Token\Entity::TOKEN, '=', $token)
                    ->first();
    }


    public function getByTokenIdAndCustomerId(string $tokenId, string $customerId)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customerId)
                    ->where(Token\Entity::ID, '=', $tokenId)
                    ->firstOrFailPublic();
    }

    public function getByGatewayTokenAndCustomerId($gatewayToken, $customerId)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customerId)
                    ->where(Token\Entity::GATEWAY_TOKEN, '=', $gatewayToken)
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getDeleteTokenByGatewayToken($gatewayToken)
    {
        return $this->newQuery()
                    ->where(Token\Entity::GATEWAY_TOKEN, '=', $gatewayToken)
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->withTrashed()
                    ->first();
    }

    public function getByGatewayTokenAndMerchantId(string $gatewayToken, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Token\Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Token\Entity::GATEWAY_TOKEN, '=', $gatewayToken)
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getByGatewayTokenAndMerchantIdWithForceIndex(string $gatewayToken, string $merchantId, string $mode)
    {

        $index = Token\Entity::TOKENS_MERCHANT_ID_INDEX;

        return $this->newQuery()
            ->from(\DB::raw("`tokens` FORCE INDEX ($index)"))
            ->where(Token\Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Token\Entity::GATEWAY_TOKEN, '=', $gatewayToken)
            ->orderBy(Token\Entity::CREATED_AT, 'desc')
            ->first();
    }

    public function getByGatewayToken(string $gatewayToken)
    {
        return $this->newQuery()
            ->where(Token\Entity::GATEWAY_TOKEN, '=', $gatewayToken)
            ->orderBy(Token\Entity::CREATED_AT, 'desc')
            ->first();
    }


    public function getByWalletTerminalAndCustomerId($wallet, $terminal, $customer)
    {
        return $this->newQuery()
                    ->where(Token\Entity::WALLET, '=', $wallet)
                    ->where(Token\Entity::TERMINAL_ID, '=', $terminal)
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer)
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getByMethodAndMerchant($method, $merchant)
    {
       return $this->newQuery()
                    ->where(Entity::METHOD, '=', $method)
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->orderBy(Token\Entity::ID, 'desc')
                    ->get();
    }

    public function getByMethodAndCustomerId($method, $customer)
    {
        return $this->newQuery()
                    ->where(Entity::METHOD, '=', $method)
                    ->where(Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Entity::MERCHANT_ID, '=', $customer->merchant->getId())
                    ->orderBy(Token\Entity::CREATED_AT, 'desc')
                    ->orderBy(Token\Entity::ID, 'desc')
                    ->get();
    }

    /**
     *  Function to return tokens by method, customerId and merchantId
     *  Not using customer's merchantId since customer's merchantId and token's merchantId
     *      can be different for dual vault tokens i.e. local tokens on global customer
     */
    public function getByMethodAndCustomerIdAndMerchantId($method, $customerId, $merchantId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::METHOD, '=', $method)
            ->where(Entity::CUSTOMER_ID, '=', $customerId)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->whereNotNull(Entity::USED_AT)
            ->where(static function (Builder $query) {
                $query->whereNull(Entity::EXPIRED_AT)
                    ->orWhere(Entity::EXPIRED_AT, '>', time());
            })
            ->orderBy(Entity::ID, 'desc')
            ->with('card')
            ->get();
    }

    /**
     * @param $method
     * @param $customer
     * @param $vpaId
     *
     * This is the method to get token by merchant id, customer id and vpa id
     *
     * @return mixed
     */
    public function getByMethodCustomerIdAndVpaId($method, $customer, $vpaId)
    {
	return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
		->where(Entity::METHOD, '=', $method)
		->where(Entity::CUSTOMER_ID, '=', $customer->getId())
		->where(Entity::MERCHANT_ID, '=', $customer->merchant->getId())
		->where(Entity::VPA_ID, '=', $vpaId)
		->orderBy(Token\Entity::CREATED_AT, 'desc')
		->orderBy(Token\Entity::ID, 'desc')
		->first();
    }


    public function getByMethodAndCustomerIdIsNull ($method , $merchantId ,$vaultToken)
    {
         $tokenCardIdColumn = $this->repo->token->dbColumn(Token\Entity::CARD_ID);
         $tokenMerchantIdColumn = $this->repo->token->dbColumn(Token\Entity::MERCHANT_ID);
         $tokenCreatedAtColumn = $this->repo->token->dbColumn(Token\Entity::CREATED_AT);
         $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);
         $cardIdColumn = $this->repo->card->dbColumn(Card\Entity::ID);

        $connectionName = $this->getSlaveConnection();

        if ((bool) ConfigKey::get(ConfigKey::CARD_ARCHIVAL_FALLBACK_ENABLED, false))
        {
            $connectionName = $this->getConnectionFromType(ConnectionType::ARCHIVED_DATA_REPLICA);
        }

         return $this->newQueryWithConnection($connectionName)
                     ->select($this->repo->token->dbColumn('*'))
                     ->join(Table::CARD, $tokenCardIdColumn, '=', $cardIdColumn)
                     ->where(Entity::METHOD, '=', $method)
                     ->where($tokenMerchantIdColumn, '=', $merchantId)
                     ->where(Card\Entity::VAULT_TOKEN,'=' , $vaultToken)
                     ->whereNull(Entity::CUSTOMER_ID)
                     ->orderBy($tokenCreatedAtColumn, 'desc')
                     ->orderBy($tokenIdColumn, 'desc')
                     ->get();
    }

    public function getByMethodAndCustomerIdAndCardIds($method, $customer, $cardIds, $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::METHOD, '=', $method)
                    ->where(Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::RECURRING,'=', '0')
                    ->whereIn(Entity::CARD_ID, $cardIds)
                    ->where(function($query)
                    {
                        $query->whereNull(Token\Entity::EXPIRED_AT)
                              ->orWhere(Token\Entity::EXPIRED_AT, '>', time());
                    })
                    ->orderBy(Token\Entity::CREATED_AT)
                    ->orderBy(Token\Entity::ID)
                    ->get();
    }

    public function getTokenByIdAndAccountNumber(string $tokenId, string $accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::METHOD, Method::EMANDATE)
                    ->where(Entity::ID, $tokenId)
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->firstOrFail();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchRecurringTokensByMerchant(array $input, string $merchantId) : Base\PublicCollection
    {
        $index = Token\Entity::TOKENS_MERCHANT_ID_INDEX;

        $query = $this->newQuery()
                      ->from(\DB::raw("`tokens` FORCE INDEX ($index)"))
                      ->where(Token\Entity::MERCHANT_ID, '=', $merchantId)
                      ->whereIn(
                          Token\Entity::RECURRING_STATUS,
                          [
                              RecurringStatus::CONFIRMED,
                              RecurringStatus::REJECTED,
                              RecurringStatus::INITIATED,
                              RecurringStatus::PAUSED,
                              RecurringStatus::CANCELLED
                          ])
                      ->with('customer');

        $query = $this->buildFetchQuery($query, $input);

        return $query->get();
    }


    /**
     * @param string $gateway
     * @param int $from
     * @param int $to
     * @return
     */
    public function fetchPendingEmandateRegistrationOptimised(string $gateway, int $from, int $to)
    {
        try
        {
            $variant = $this->app['razorx']->getTreatment(
                $from,
                Merchant\RazorxTreatment::FETCH_PENDING_EMANDATE_REGISTRATION_FROM_WDA,
                $this->app['rzp.mode']
            );

            if($variant === 'on')
            {
                return $this->fetchPendingEmandateRegistrationFromWDA($gateway, $from, $to);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $paymentTokenIdColumn = $this->repo->payment->dbColumn(Payment\Entity::TOKEN_ID);

        $paymentGlobalTokenIdColumn = $this->repo->payment->dbColumn(Payment\Entity::GLOBAL_TOKEN_ID);

        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentAuthorizedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::AUTHORIZED_AT);

        $paymentTableName = $this->repo->payment->getTableName();

        $selectCols = $this->dbColumn('*');

        $connection = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $localTokenQuery = $this->newQueryWithConnection($connection)
            ->select($selectCols, 'payments.id as payment_id')
            ->join($paymentTableName, function ($join) use($paymentTokenIdColumn, $tokenIdColumn, $paymentAuthorizedAtColumn, $from, $to)
            {
                $join->on($tokenIdColumn, '=', $paymentTokenIdColumn)
                    ->whereBetween('payments.authorized_at', [$from, $to]);
            })
            ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::INITIAL)
            ->where($paymentRecurringColumn, '=', 1)
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where($paymentGatewayColumn, '=', $gateway)
            ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::INITIATED)
            ->where($tokenRecurringColumn, '!=', 1)
            ->with(['customer', 'merchant']);

        $globalTokenQuery = $this->newQueryWithConnection($connection)
            ->select($selectCols, 'payments.id as payment_id')
            ->join($paymentTableName, function ($join) use ($tokenIdColumn, $paymentGlobalTokenIdColumn, $paymentAuthorizedAtColumn, $from, $to)
            {
                $join->on($tokenIdColumn, '=', $paymentGlobalTokenIdColumn)
                    ->whereBetween($paymentAuthorizedAtColumn, [$from, $to]);
            })
            ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::INITIAL)
            ->where($paymentRecurringColumn, '=', 1)
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where($paymentGatewayColumn, '=', $gateway)
            ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::INITIATED)
            ->where($tokenRecurringColumn, '!=', 1)
            ->with(['customer', 'merchant']);

        return $localTokenQuery->union($globalTokenQuery)->get();
    }

    /**
     * @param string $gateway
     * @param int $from
     * @param int $to
     * @return mixed
     */
    public function fetchPendingEmandateRegistrationFromWDA(string $gateway, int $from, int $to)
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'function'     => __FUNCTION__,
            'input_params' => ['from' => $from, 'to' => $to, 'gateway' => $gateway],
            'route_name'   => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $statement = "(SELECT tokens.*, payments.id as payment_id FROM tokens INNER JOIN payments ON tokens.id = payments.token_id AND payments.authorized_at >= @val2 AND payments.authorized_at <= @val3 WHERE payments.recurring_type = 'initial' AND payments.recurring = 1 AND payments.method = 'emandate' AND payments.gateway = @val1 AND recurring_status = 'initiated' AND tokens.recurring != 1 AND tokens.deleted_at is null) UNION (SELECT tokens.*, payments.id as payment_id FROM tokens inner join payments on tokens.id = payments.global_token_id AND payments.authorized_at >= @val2 AND payments.authorized_at <= @val3 WHERE payments.recurring_type = 'initial' AND payments.recurring = 1 AND payments.method = 'emandate' AND payments.gateway = @val1 AND recurring_status = 'initiated' AND tokens.recurring != 1 AND tokens.deleted_at is null)";
        $parameter = ['val1' => $gateway, 'val2' => $from, 'val3' => $to];

        $wdaRegisterQuery = new WDARegisterQueryRequestBuilder();

        $wdaRegisterQuery->sqlStatement($statement);
        $wdaRegisterQuery->params($parameter);
        $wdaRegisterQuery->setCluster(Cluster::ADMIN_CLUSTER);

        $registerData = $wdaClient->registerQuery($wdaRegisterQuery->build());

        $queryResponseData =  $wdaClient->execRegisteredQuery($registerData);

        $collection = new Base\PublicCollection();

        foreach ($queryResponseData->getEntities() as $entity)
        {
            $token = json_decode($entity->serializeToJsonString(), true);

            $merchant = $this->repo->merchant->find($token['merchant_id']);
            $customer = $this->repo->customer->findById($token['customer_id']);

            $token->customer()->associate($customer);
            $token->merchant()->associate($merchant);

            $collection->push($token);
        }

        return $collection;
    }

    /**
     * @throws ServerErrorException
     */
    public function fetchPendingEmandateRegistration(string $gateway, int $from, int $to)
    {
        $paymentTokenIdColumn = $this->repo->payment->dbColumn(Payment\Entity::TOKEN_ID);

        $paymentGlobalTokenIdColumn = $this->repo->payment->dbColumn(Payment\Entity::GLOBAL_TOKEN_ID);

        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentAuthorizedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::AUTHORIZED_AT);

        $paymentTableName = $this->repo->payment->getTableName();

        $selectCols = $this->dbColumn('*');

        $subQuery = Payment\Entity::query()
                ->select($this->repo->payment->dbColumn('*'))
                ->whereBetween($paymentAuthorizedAtColumn, [$from, $to]);

        $connection = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        return $this->newQueryWithConnection($connection)
            ->select($selectCols, 'payments.id as payment_id')
            ->joinSub(
                $subQuery,
                $paymentTableName,
                function ($join) use($paymentTokenIdColumn, $paymentGlobalTokenIdColumn, $tokenIdColumn)
                {
                    $join->on($tokenIdColumn, '=', $paymentTokenIdColumn);
                    $join->orOn($tokenIdColumn, '=', $paymentGlobalTokenIdColumn);
                }
            )
            ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::INITIAL)
            ->where($paymentRecurringColumn, '=', 1)
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where($paymentGatewayColumn, '=', $gateway)
            ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::INITIATED)
            ->where($tokenRecurringColumn, '!=', 1)
            ->with(['customer', 'merchant'])
            ->get();
    }

    /**
     * @throws ServerErrorException
     */
    public function fetchPendingEMandateDebit(string $gateway, $from, $to)
    {
        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentStatusColumn = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $selectCols = $this->dbColumn('*');

        return $this->newQueryOnPaymentFetchReplica(600000)
                    ->select($selectCols,
                             'payments.id as payment_id',
                             'payments.amount as payment_amount',
                             'payments.created_at as payment_created_at',
                             'payments.email as payment_email')
                    ->from(\DB::raw('`tokens`, `payments`'))
                    ->where($tokenIdColumn, '=', \DB::raw('`payments`.`token_id`'))
                    ->whereBetween($paymentCreatedAtColumn, [$from, $to])
                    ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::AUTO)
                    ->where($paymentRecurringColumn, '=', 1)
                    ->where($paymentMethodColumn, '=', Method::EMANDATE)
                    ->where($paymentStatusColumn, '=', Payment\Status::CREATED)
                    ->where($paymentGatewayColumn, '=', $gateway)
                    ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
                    ->where($tokenRecurringColumn, '=', 1)
                    ->with(['merchant', 'terminal'])
                    ->get();
    }

    // TODO: need to optimize the query futher
    public function fetchDeletedTokensForMethods(array $methods, $gateways, string $acquirer, $from, $to): Base\PublicCollection
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $selectCols = $this->dbColumn('*');

        $tokenMethodColumn = $this->repo->token->dbColumn(Entity::METHOD);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $tokenDeletedAtColumn = $this->repo->token->dbColumn(Entity::DELETED_AT);

        $tokenTerminalIdColumn = $this->repo->token->dbColumn(Entity::TERMINAL_ID);

        $terminalGatewayColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY);

        $terminalGatewayAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->select($selectCols)
                    ->from(\DB::raw('`tokens`, `terminals`'))
                    ->where($tokenTerminalIdColumn, '=', \DB::raw('`terminals`.`id`'))
                    ->whereBetween($tokenDeletedAtColumn, [$from, $to])
                    ->whereIn($tokenMethodColumn, $methods)
                    ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
                    ->where($tokenRecurringColumn, '=', 1)
                    ->whereIn($terminalGatewayColumn, $gateways)
                    ->where($terminalGatewayAcquirerColumn, '=', $acquirer)
                    ->withTrashed()
                    ->get();
    }

    /**
     * @param string $gateway
     * @param $from
     * @param $to
     * @param $acquirer
     * @return
     * @throws ServerErrorException
     */
    public function newFetchPendingEMandateDebitWithGatewayAcquirer(string $gateway, $from, $to, $acquirer)
    {
        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentStatusColumn = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $terminalAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $tokenTerminalIdColumn = $this->repo->token->dbColumn(Entity::TERMINAL_ID);

        return $this->newQueryOnPaymentFetchReplica(600000)
            ->select('tokens.' . Entity::ACCOUNT_TYPE,
                'tokens.' . Entity::BENEFICIARY_NAME,
                'tokens.' . Entity::IFSC,
                'tokens.' . Entity::ACCOUNT_NUMBER,
                'tokens.' . Entity::GATEWAY_TOKEN,
                'tokens.' . Entity::MERCHANT_ID,
                'tokens.' . Entity::TERMINAL_ID,
                'payments.id as payment_id',
                'payments.amount as payment_amount',
                'payments.notes as payment_notes',
                'payments.created_at as payment_created_at',
                'payments.email as payment_email')
            ->from(\DB::raw('`tokens`, `payments`, `terminals`'))
            ->where($tokenIdColumn, '=', \DB::raw('`payments`.`token_id`'))
            ->where($tokenTerminalIdColumn, '=', \DB::raw('`terminals`.`id`'))
            ->whereBetween($paymentCreatedAtColumn, [$from, $to])
            ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::AUTO)
            ->where($paymentRecurringColumn, '=', 1)
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where($paymentGatewayColumn, '=', $gateway)
            ->where($paymentStatusColumn, '=', Payment\Status::CREATED)
            ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
            ->where($tokenRecurringColumn, '=', 1)
            ->where($terminalAcquirerColumn, '=', $acquirer)
            ->with(['merchant'])
            ->get();
    }

    /**
     * @throws ServerErrorException
     */
    public function fetchPendingEMandateDebitWithGatewayAcquirer(string $gateway, $from, $to, $acquirer)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentStatusColumn = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $terminalAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $tokenTerminalIdColumn = $this->repo->token->dbColumn(Entity::TERMINAL_ID);

        $selectCols = $this->dbColumn('*');

        return $this->newQueryOnPaymentFetchReplica(600000)
                    ->select($selectCols,
                            'payments.id as payment_id',
                            'payments.amount as payment_amount',
                            'payments.created_at as payment_created_at',
                            'payments.email as payment_email')
                    ->from(\DB::raw('`tokens`, `payments`, `terminals`'))
                    ->where($tokenIdColumn, '=', \DB::raw('`payments`.`token_id`'))
                    ->where($tokenTerminalIdColumn, '=', \DB::raw('`terminals`.`id`'))
                    ->whereBetween($paymentCreatedAtColumn, [$from, $to])
                    ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::AUTO)
                    ->where($paymentRecurringColumn, '=', 1)
                    ->where($paymentMethodColumn, '=', Method::EMANDATE)
                    ->where($paymentGatewayColumn, '=', $gateway)
                    ->where($paymentStatusColumn, '=', Payment\Status::CREATED)
                    ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
                    ->where($tokenRecurringColumn, '=', 1)
                    ->where($terminalAcquirerColumn, '=', $acquirer)
                    ->with(['merchant', 'terminal'])
                    ->get();
    }

    /**
     * @throws ServerErrorException
     */
    public function fetchPendingNachRegistration(string $gateway, int $from, int $to): Base\PublicCollection
    {
        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $selectCols = $this->dbColumn('*');

        $tokens = $this->newQueryOnPaymentFetchReplica(600000)
                        ->select($selectCols, 'payments.id as payment_id')
                        ->from(\DB::raw('`tokens`, `payments`'))
                        ->where($tokenIdColumn, '=', \DB::raw('`payments`.`token_id`'))
                        ->whereBetween($paymentCreatedAtColumn, [$from, $to])
                        ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::INITIAL)
                        ->where($paymentRecurringColumn, '=', 1)
                        ->where($paymentMethodColumn, '=', Method::NACH)
                        ->where($paymentGatewayColumn, '=', $gateway)
                        ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::INITIATED)
                        ->where($tokenRecurringColumn, '!=', 1)
                        ->with(['customer', 'merchant'])
                        ->get();

        return (new Entity)->mapIFSC($tokens);
    }

    // unused as of now
    public function fetchPendingNachDebit(string $gateway, $from, $to)
    {
        $paymentTokenIdColumn = $this->repo->payment->dbColumn(Payment\Entity::TOKEN_ID);

        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentStatusColumn = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $paymentTableName = $this->repo->payment->getTableName();

        $selectCols = $this->dbColumn('*');

        $payments = Payment\Entity::query()
            ->select($this->repo->payment->dbColumn('*'))
            ->whereBetween($paymentCreatedAtColumn, [$from, $to]);

        $tokens = $this->newQueryOnSlave(600000)
                        ->select($selectCols,
                            'payments.id as payment_id',
                            'payments.amount as payment_amount',
                            'payments.created_at as payment_created_at',
                            'payments.email as payment_email')
                        ->joinSub(
                            $payments,
                            $paymentTableName,
                            function ($join) use($paymentTokenIdColumn, $tokenIdColumn)
                            {
                                $join->on($tokenIdColumn, '=', $paymentTokenIdColumn);
                            }
                        )
                        ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::AUTO)
                        ->where($paymentRecurringColumn, '=', 1)
                        ->where($paymentMethodColumn, '=', Method::NACH)
                        ->where($paymentStatusColumn, '=', Payment\Status::CREATED)
                        ->where($paymentGatewayColumn, '=', $gateway)
                        ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
                        ->where($tokenRecurringColumn, '=', 1)
                        ->with(['merchant', 'terminal'])
                        ->get();

        return (new Entity)->mapIFSC($tokens);
    }

    /**
     * @throws ServerErrorException
     */
    public function fetchPendingNachOrMandateDebit($gateways, $from, $to, $acquirer)
    {

        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentStatusColumn = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $terminalAcquirerColumn = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $tokenTerminalIdColumn = $this->repo->token->dbColumn(Entity::TERMINAL_ID);

        return $this->newQueryOnPaymentFetchReplica(600000)
              ->select('tokens.' . Entity::ACCOUNT_TYPE,
                       'tokens.' . Entity::BENEFICIARY_NAME,
                       'tokens.' . Entity::IFSC,
                       'tokens.' . Entity::ACCOUNT_NUMBER,
                       'tokens.' . Entity::GATEWAY_TOKEN,
                       'tokens.' . Entity::MERCHANT_ID,
                       'tokens.' . Entity::TERMINAL_ID,
                       'payments.id as payment_id',
                       'payments.amount as payment_amount',
                       'payments.notes as payment_notes',
                       'payments.created_at as payment_created_at',
                       'payments.email as payment_email')
              ->from(\DB::raw('`tokens`, `payments`, `terminals`'))
              ->where($tokenIdColumn, '=', \DB::raw('`payments`.`token_id`'))
              ->where($tokenTerminalIdColumn, '=', \DB::raw('`terminals`.`id`'))
              ->whereBetween($paymentCreatedAtColumn, [$from, $to])
              ->where($paymentRecurringTypeColumn, '=', Payment\RecurringType::AUTO)
              ->where($paymentRecurringColumn, '=', 1)
              ->whereIn($paymentMethodColumn, [Method::NACH, Method::EMANDATE])
              ->whereIn($paymentGatewayColumn, $gateways)
              ->where($paymentStatusColumn, '=', Payment\Status::CREATED)
              ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
              ->where($tokenRecurringColumn, '=', 1)
              ->where($terminalAcquirerColumn, '=', $acquirer)
              ->with(['merchant'])
              ->get();
    }

    public function getByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        Entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->find($id);
    }

    public function findOrFailByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        Entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->findOrFailPublic($id);
    }

    public function fetchByMethodAndCardIdAndMerchant($method,string $cardId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Token\Entity::METHOD, '=', $method)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Token\Entity::CARD_ID, '=', $cardId)
                    ->where(function($query)
                    {
                        $query->whereNull(Token\Entity::EXPIRED_AT)
                            ->orWhere(Token\Entity::EXPIRED_AT, '>', time());
                    })
                    ->first();

    }

    public function getByMethodAndCustomerIdAndCardIdAndMerchantId($method, $customerId, $cardId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Token\Entity::METHOD, '=', $method)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customerId)
                    ->where(Token\Entity::CARD_ID, '=', $cardId)
                    ->where(function($query)
                    {
                        $query->whereNull(Token\Entity::EXPIRED_AT)
                            ->orWhere(Token\Entity::EXPIRED_AT, '>', time());
                    })
                    ->first();
    }

    public function findOrFailTrashedById($tokenId)
    {
        return $this->newQuery()
                    ->withTrashed()
                    ->findOrFailPublic($tokenId);
    }

    public function getDomesticTokensWithoutCardMandateToPause($count = 100)
    {
        $tokenCardId = $this->repo->token->dbColumn(Token\Entity::CARD_ID);
        $cardId = $this->repo->card->dbColumn(Card\Entity::ID);
        $cardCountry = $this->repo->card->dbColumn(Card\Entity::COUNTRY);
        $tokenCreatedAt = $this->repo->token->dbColumn(Token\Entity::CREATED_AT);
        $tokenId = $this->repo->token->dbColumn(Token\Entity::ID);

        return $this->newQuery()
                    ->where(Token\Entity::METHOD, '=', Payment\Method::CARD)
                    ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::CONFIRMED)
                    ->whereNull(Token\Entity::CARD_MANDATE_ID)
                    ->join(Table::CARD, $cardId, '=', $tokenCardId)
                    ->where($cardCountry, '=', Card\IIN\Country::IN)
                    ->orderBy($tokenCreatedAt)
                    ->limit($count)
                    ->get($tokenId);
    }

    public function updateById($tokenId, $updateData)
    {
        return $this->newQuery()
            ->where(Token\Entity::ID, $tokenId)
            ->update($updateData);
    }

    public function bulkUpdateTokenIdsConsent(string $merchantId, array $tokenIds, int $consentTimestamp)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->whereIn(Entity::ID, $tokenIds)
                    ->whereNull(Token\Entity::ACKNOWLEDGED_AT)
                    ->update(
                        [
                            TOKEN\Entity::ACKNOWLEDGED_AT => $consentTimestamp
                        ]
                    );
    }

    /**
     * This function is used to validate the following -
     * Given a merchantId and list of tokenIds
     * 1. filter card method tokenIds
     * 2. filter given merchant's tokenIds
     *
     * @param string $merchantId The Primary key of the Merchant whose Token
     *                           entities are being fetched
     * @param array  $tokenIds   The Primary keys of Token entity (or) `token`
     *                           column values from Token entity
     *
     * @return Base\PublicCollection
     */
    public function filterMerchantCardTokens(string $merchantId, array $tokenIds): Base\PublicCollection
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select(Entity::ID, Entity::TOKEN)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Token\Entity::METHOD, Payment\Method::CARD)
                    ->where(function ($query) use ($tokenIds)
                    {
                        $query->whereIn(Entity::ID, $tokenIds)
                              ->orWhereIn(Entity::TOKEN, $tokenIds);
                    })
                    ->get();
    }

    /**
     * Fetch a list of global token ids which have received consents for token
     * provisioning.
     *
     * @param array  $networks List of network names which support global tokens.
     * @param string $offset   Last processed global token id.
     * @param int    $limit    Number of tokens to fetch.
     *
     * @return array List of global token ids
     */
    public function fetchConsentReceivedGlobalTokenIds(array $networks, string $offset, int $limit): array
    {
        if (empty($networks)) {
            return [];
        }

        // SELECT t.id
        // FROM   tokens t
        //        INNER JOIN cards c
        //                ON t.card_id = c.id
        // WHERE  t.method = 'card'
        //     AND t.acknowledged_at IS NOT NULL
        //     AND t.merchant_id = '100000Razorpay'
        //     AND c.network IN ('MasterCard', 'Visa')
        //     AND c.vault = 'rzpvault'
        //     AND (c.international = 0 OR c.international IS NULL)
        //     AND t.id > 'last_cron_token_id'
        // ORDER BY t.id ASC
        // LIMIT  1000;

        $cardsTable               = $this->repo->card->getTableName();
        $cardsIdColumn            = $this->repo->card->dbColumn(Card\Entity::ID);
        $cardsInternationalColumn = $this->repo->card->dbColumn(Card\Entity::INTERNATIONAL);
        $cardsNetworkColumn       = $this->repo->card->dbColumn(Card\Entity::NETWORK);
        $cardsVaultColumn         = $this->repo->card->dbColumn(Card\Entity::VAULT);

        $tokensAcknowledgedAtColumn = $this->dbColumn(Entity::ACKNOWLEDGED_AT);
        $tokensCardIdColumn         = $this->dbColumn(Entity::CARD_ID);
        $tokensIdColumn             = $this->dbColumn(Entity::ID);
        $tokensMerchantIdColumn     = $this->dbColumn(Entity::MERCHANT_ID);
        $tokensMethodColumn         = $this->dbColumn(Entity::METHOD);
        $tokensExpiredAtColumn      = $this->dbColumn(Entity::EXPIRED_AT);

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->join($cardsTable, $tokensCardIdColumn, '=', $cardsIdColumn)
            ->where([
                $cardsVaultColumn => Card\Vault::RZP_VAULT,
                $tokensMethodColumn => Entity::CARD,
                $tokensMerchantIdColumn => Merchant\Account::SHARED_ACCOUNT,
            ])
            ->where(static function (Builder $query) use ($cardsInternationalColumn) {
                 $query->where($cardsInternationalColumn, '=', 0)
                    ->orWhereNull($cardsInternationalColumn);
            })
            ->where(static function (Builder $query) use ($tokensExpiredAtColumn) {
                $query->whereNull($tokensExpiredAtColumn)
                    ->orWhere($tokensExpiredAtColumn, '>', time());
            })
            ->whereIn($cardsNetworkColumn, $networks)
            ->whereNotNull($tokensAcknowledgedAtColumn)
            ->where($tokensIdColumn, '>', $offset)
            ->orderBy($tokensIdColumn)
            ->limit($limit)
            ->pluck($tokensIdColumn)
            ->toArray();
    }

    public function findManyOnReadReplica(array $tokenIds)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->with(['card', 'customer'])
            ->findMany($tokenIds);
    }

    public function getByTokensAndCustomer(array $tokenIds, string $customerId)
    {
        return $this->newQuery()
            ->where(Token\Entity::CUSTOMER_ID, '=', $customerId)
            ->whereIn(Token\Entity::ID, $tokenIds)
            ->get();
    }

    public function getCountOfExistingTokensByTokensAndCustomer(array $tokenIds, string $customerId)
    {
        return $this->newQuery()
            ->where(Token\Entity::CUSTOMER_ID, '=', $customerId)
            ->whereIn(Token\Entity::ID, $tokenIds)
            ->count();
    }

    /**
     * Fetch a list of global customer local token data (token id, merchant id, card network)
     * which have received consents for token provisioning from data lake.
     *
     * @param array  $supportedNetworks List of network names which support tokens provisioning.
     * @param string $offset   Last processed token id.
     * @param int    $limit    Number of tokens to fetch.
     *
     * @return array List of global token ids
     */
    public function fetchConsentReceivedGlobalCustomerLocalTokensDataFromDataLake(array $supportedNetworks, string $offset, int $limit): array
    {
        $supportedNetworksInString = implode("','", $supportedNetworks);

        $firstJune2022 = '2022-06-01';

        $rawQueryBuilder =<<<'EOT'
            SELECT t.id, t.merchant_id, c.network FROM hive.realtime_hudi_api.tokens t
            INNER JOIN hive.realtime_hudi_api.cards c
                ON t.card_id = c.id
            INNER JOIN hive.realtime_hudi_api.customers cust
                ON t.customer_id = cust.id
            WHERE t.method = 'card'
                AND t.acknowledged_at IS NOT NULL
                AND cust.merchant_id = '100000Razorpay'
                AND t.merchant_id != '100000Razorpay'
                AND c.network IN ('%s')
                AND c.vault = 'rzpvault'
                AND (c.international = 0 OR c.international IS NULL)
                AND (t.expired_at > %d OR t.expired_at IS NULL)
                AND (t.created_date > '%s' AND c.created_date > '%s')
                AND t.deleted_at IS NULL
                AND t.id > '%s'
            ORDER BY t.id ASC
            LIMIT %d
EOT;

        $rawQuery = sprintf(
            $rawQueryBuilder,
            $supportedNetworksInString,
            time(),
            $firstJune2022,
            $firstJune2022,
            $offset,
            $limit
        );

        return $this->app['datalake.presto']->getDataFromDataLake($rawQuery);
    }
}
