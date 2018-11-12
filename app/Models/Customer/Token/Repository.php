<?php

namespace RZP\Models\Customer\Token;

use DB;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Method;

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

    public function getByCustomer($customer)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->whereNotNull(Token\Entity::USED_AT)
                    ->where(function($query)
                    {
                        $query->whereNull(Token\Entity::EXPIRED_AT)
                              ->orWhere(Token\Entity::EXPIRED_AT, '>', time());
                    })
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

    public function getByWalletTerminalAndCustomerId($wallet, $terminal, $customer)
    {
        return $this->newQuery()
                    ->where(Token\Entity::WALLET, '=', $wallet)
                    ->where(Token\Entity::TERMINAL_ID, '=', $terminal)
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer)
                    ->first();
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
                      ->where(function($query)
                      {
                          $query->where(Token\Entity::RECURRING, '=', "1")
                                ->orWhere(function($query)
                                {
                                    $query->where(Token\Entity::RECURRING, '=', "0")
                                          ->whereIn(
                                              Token\Entity::RECURRING_STATUS,
                                              [RecurringStatus::CONFIRMED, RecurringStatus::REJECTED]);
                                });
                      })
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

        return $this->newQuery()
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

        return $this->newQuery()
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
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where($paymentGatewayColumn, '=', $gateway)
            ->where($paymentStatusColumn, '=', Payment\Status::CREATED)
            ->where(Entity::RECURRING_STATUS, '=', RecurringStatus::CONFIRMED)
            ->where($tokenRecurringColumn, '=', 1)
            ->with(['merchant', 'terminal'])
            ->get();
    }
}
