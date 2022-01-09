<?php

namespace RZP\Models\Customer\Token;

use DB;

use RZP\Models\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Method;
use RZP\Exception\ServerErrorException;

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

    public function getByCustomer($customer, bool $withVpas = false)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->whereNotNull(Token\Entity::USED_AT)
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

        $index = Token\Entity::TOKENS_MERCHANT_ID_INDEX_LIVE;

        if ($mode === 'test')
        {
            $index = Token\Entity::TOKENS_MERCHANT_ID_INDEX_TEST;
        }

        return $this->newQuery()
            ->from(\DB::raw("`tokens` FORCE INDEX ($index)"))
            ->where(Token\Entity::MERCHANT_ID, '=', $merchantId)
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

    public function getByMethodAndCustomerIdAndCardIds($method, $customer, $cardIds)
    {
        return $this->newQuery()
                    ->where(Entity::METHOD, '=', $method)
                    ->where(Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Entity::MERCHANT_ID, '=', $customer->merchant->getId())
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
        $query = $this->newQuery()
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

        return $this->newQueryOnSlave(600000)
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

        return $this->newQueryOnSlave(600000)
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
     * @throws ServerErrorException
     */
    public function fetchPendingEMandateDebitWithGatewayAcquirer(string $gateway, $from, $to, $acquirer)
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

        $selectCols = $this->dbColumn('*');

        return $this->newQueryOnSlave(600000)
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

    public function fetchPendingNachRegistration(string $gateway, int $from, int $to)
    {
        $paymentRecurringTypeColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING_TYPE);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentGatewayColumn = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $tokenIdColumn = $this->repo->token->dbColumn(Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Entity::RECURRING);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $selectCols = $this->dbColumn('*');

        $tokens = $this->newQueryOnSlave(600000)
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

        $selectCols = $this->dbColumn('*');

        return $this->newQueryOnSlave(600000)
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
}
