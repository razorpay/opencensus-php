<?php

namespace RZP\Models\P2p\Transaction;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Exception\BadRequestValidationFailureException;

class Rules
{
    const FIRST_TRANSACTION_EXISTS  = 'first_transaction_exists';
    const FIRST_TRANSACTION_AMOUNT  = 'first_transaction_amount';
    const FIRST_TRANSACTION_TIME    = 'first_transaction_time';
    const TOTAL_TRANSACTION_AMOUNT  = 'total_transaction_amount';
    const COLLECT_REQUESTS_CREATED_COUNT = 'collect_requests_created_count';

    // In Paisa
    const MAX_AMOUNT_ALLOWED_IN_COOLDOWN = 500000;

    // In Paisa
    const MAX_AMOUNT_ALLOWED_ON_FIRST_TRANSACTION = 500000;

    // In Paisa
    const MAX_AMOUNT_ALLOWED_IN_COLLECT_REQUEST = 200000;

    // In Seconds
    const MAX_COOLDOWN_PERIOD = 86400;

    // Number of collect requests
    const MAX_COLLECT_REQUESTS_ALLOWED_PER_DAY = 5;

    protected $data = [
        'first_transaction_exists'  => null,
        'first_transaction_amount'  => null,
        'first_transaction_time'    => null,
        'total_transaction_amount'  => null,
        'collect_requests_created_count' => null,
    ];

    protected $rules = [
        'rmd001' => [
            'function' => 'rmd001_applicable_check',
            'values'   => [
                0 => true,
                1 => [
                    'function' => 'first_transaction_check',
                    'values' => [
                        0 => [
                            'function' => 'new_transaction_amount_exceeds_check',
                            'values' => [
                                0 => 'First transaction can not be more than %s rupees.',
                                1 => true,
                            ],
                        ],
                        1 => [
                            'function' => 'cooldown_period_check',
                            'values' => [
                                0 => true,
                                1 => [
                                    'function' => 'cooldown_amount_exceeds_check',
                                    'values' => [
                                        0 => 'Allowed limit of %s exceeded in cooldown of %s hours.',
                                        1 => true,
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'collect'=>[
            'function' => 'collect_request_check',
            'values' => [
                0 => true,
                1 => [
                    'function' => 'collect_request_amount_exceeds_check',
                    'values' => [
                        0 => 'Maximum per collect transaction limit is Rs %s.',
                        1 => true,
                    ],
                ],
            ],
        ],
        'collect_per_day_check' =>[
            'function' => 'collect_request_check',
            'values' => [
                0 => true,
                1=> [
                    'function' => 'collect_request_per_day_check_applicable',
                    'values' =>[
                        0 => true,
                        1 => [
                            'function' => 'collect_requests_per_day_check',
                            'values' => [
                                0 => 'You have exceeded the allowable limit of collect request generation. Please try after 24 hours.',
                                1 => true,
                            ],
                        ]
                    ]

                ],
            ],

        ],

        'payer_payee_check' => [
            'function' => 'payer_payee_check_applicable',
            'values'   => [
                0 => true,
                1 => [
                    'function' => 'is_payer_payee_not_same',
                    'values' => [
                        0 => 'Payer and Payee should not be same',
                        1 => [
                            'function' => 'is_payer_payee_bank_account_not_same',
                            'values' => [
                                0 => 'Payer and Payee bank account should not be same',
                                1 => true
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var null
     */
    protected $currentRule      = null;

    /**
     * @var null
     */
    protected $currentFunction  = null;

    /**
     * @var Entity
     */
    protected $transaction;

    public function __construct(Entity $transaction)
    {
        $this->transaction = $transaction;
    }

    public function validate()
    {
        foreach ($this->rules as $name => $rule)
        {
            $this->currentRule = $name;

            $output = $this->run($rule);

            if ($output === true)
            {
                continue;
            }

            $error = $this->currentFunction['values'][0];

            throw new BadRequestValidationFailureException($error, null, [
                Entity::DATA            => $this->data,
                Entity::TRANSACTION     => $this->transaction->toArrayTrace(),
            ]);
        }
    }

    protected function rmd001ApplicableCheck(): bool
    {
        return ($this->transaction->getFlow() === Flow::DEBIT);
    }

    protected function firstTransactionCheck(): bool
    {
        return $this->retrieveDataValue(self::FIRST_TRANSACTION_EXISTS);
    }

    protected function newTransactionAmountExceedsCheck(): bool
    {
        $this->fillMessages([self::MAX_AMOUNT_ALLOWED_ON_FIRST_TRANSACTION / 100]);

        return ($this->transaction->getAmount() <= self::MAX_AMOUNT_ALLOWED_ON_FIRST_TRANSACTION);
    }

    protected function cooldownPeriodCheck(): bool
    {
        $firstTransactionTime = $this->retrieveDataValue(self::FIRST_TRANSACTION_TIME);

        $currentTime = Carbon::now()->getTimestamp();

        $diff = $currentTime - $firstTransactionTime;

        return $diff <= self::MAX_COOLDOWN_PERIOD;
    }

    protected function cooldownAmountExceedsCheck(): bool
    {
        $this->fillMessages([
            self::MAX_AMOUNT_ALLOWED_IN_COOLDOWN / 100,
            self::MAX_COOLDOWN_PERIOD / 3600
        ]);

        $totalAmount = $this->retrieveDataValue(self::TOTAL_TRANSACTION_AMOUNT);

        $currentTransactionAmount = $this->transaction->getAmount();

        return ($totalAmount + $currentTransactionAmount) <= self::MAX_AMOUNT_ALLOWED_IN_COOLDOWN;
    }

    protected function collectRequestCheck():bool
    {
        return ($this->transaction->getFlow() === Flow::CREDIT && $this->transaction->getType() === Type::COLLECT);
    }

    protected function collectRequestAmountExceedsCheck():bool
    {
        $this->fillMessages([self::MAX_AMOUNT_ALLOWED_IN_COLLECT_REQUEST / 100]);

        return ($this->transaction->getAmount() <= self::MAX_AMOUNT_ALLOWED_IN_COLLECT_REQUEST);
    }

    protected function collectRequestPerDayCheckApplicable():bool
    {
        return true;
    }

    protected function collectRequestsPerDayCheck():bool
    {
        $this->fillMessages([self::MAX_COLLECT_REQUESTS_ALLOWED_PER_DAY]);

        $collectRequestCount = $this->retrieveDataValue(self::COLLECT_REQUESTS_CREATED_COUNT);

        return $collectRequestCount < self::MAX_COLLECT_REQUESTS_ALLOWED_PER_DAY;
    }

    private function retrieveDataValue(string $key)
    {
        if ($this->data[$key] === null)
        {
            $this->setDataValues($key);
        }

        return $this->data[$key];
    }

    private function setDataValues(string $config)
    {
        switch ($config)
        {
            case self::FIRST_TRANSACTION_EXISTS:
            case self::FIRST_TRANSACTION_AMOUNT:
            case self::FIRST_TRANSACTION_TIME:
                $transaction = (new Core)->getFirstTransactionWithStatusAndFlow([
                    Status::PENDING,
                    Status::COMPLETED,
                ], Flow::DEBIT);

                if ($transaction instanceof Entity)
                {
                    $this->data[self::FIRST_TRANSACTION_EXISTS] = true;
                    $this->data[self::FIRST_TRANSACTION_AMOUNT] = $transaction->getAmount();
                    $this->data[self::FIRST_TRANSACTION_TIME]   = $transaction->getCreatedAt();
                }
                else
                {
                    $this->data[self::FIRST_TRANSACTION_EXISTS] = false;
                    $this->data[self::FIRST_TRANSACTION_AMOUNT] = 0;
                    $this->data[self::FIRST_TRANSACTION_TIME]   = 0;
                }
                break;

            case self::TOTAL_TRANSACTION_AMOUNT:
                $totalAmount = (new Core)->getTotalTransactionAmountWithStatusAndFlow([
                    Status::PENDING,
                    Status::COMPLETED
                ], Flow::DEBIT);
                $this->data[self::TOTAL_TRANSACTION_AMOUNT] = $totalAmount;
                break;

            case self::COLLECT_REQUESTS_CREATED_COUNT:
                $collectRequestsEntities = (new Core)->getCollectRequestsWithCreatedAtAndPayee(
                    Carbon::today()->timezone('Asia/Kolkata')->getTimestamp(),
                    $this->transaction->getPayeeId(),
                    Flow::CREDIT,
                    Type::COLLECT,
                    self::MAX_COLLECT_REQUESTS_ALLOWED_PER_DAY);

                $this->data[self::COLLECT_REQUESTS_CREATED_COUNT] = $collectRequestsEntities->count();
                break;
        }
    }

    private function run($function)
    {
        if (isset($function['function']) === true)
        {
            $this->currentFunction = $function;

            $method = camel_case($function['function']);

            $output = $this->{$method}();

            $picked = $function['values'][(int) $output];

            return $this->run($picked);
        }

        return $function;
    }

    private function fillMessages(array $args)
    {
        $this->currentFunction['values'][0] = sprintf($this->currentFunction['values'][0], ...$args);
    }

    public function getRules()
    {
        return $this->rules;
    }

    protected function payerPayeeCheckApplicable(): bool
    {
        return (
            ($this->transaction->payer->getDeviceId() === $this->transaction->payee->getDeviceId()) and
            ($this->transaction->getPayerType() === Vpa\Entity::VPA) and
            ($this->transaction->getPayerType() === $this->transaction->getPayeeType())
        );
    }

    protected function isPayerPayeeNotSame(): bool
    {
        return $this->transaction->payer->getId() !== $this->transaction->payee->getId();
    }

    protected function isPayerPayeeBankAccountNotSame(): bool
    {
        return $this->transaction->payer->getBankAccountId() !== $this->transaction->payee->getBankAccountId();
    }
}
