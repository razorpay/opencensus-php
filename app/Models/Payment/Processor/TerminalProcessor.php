<?php

namespace RZP\Models\Payment\Processor;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\BharatQr;
use RZP\Models\CardMandate;
use RZP\Models\BankTransfer;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Account;
use RZP\Exception\LogicException;
use RZP\Models\BankAccount\Entity;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;

class TerminalProcessor extends Base\Core
{
    protected $payment;

    const PAYMENT_HIT_ROUTING_SERVICE_AUTHENTICATION = 'payments_hit_routing_service_authentication';

    /**
     * Get list of terminals for the payment from the Terminal Selection.
     * Note: In case of a reattempt, remove the terminals that were used, since
     * these can be treated as failures from the list of terminals
     * to be selected
     *
     * @param Payment\Entity $payment
     *
     * @param array          $gatewayData
     *
     * @return array
     */
    public function getTerminalsForPayment(Payment\Entity $payment, Merchant\Entity $chargeAccountMerchant = null,
                                           CardMandate\Entity $cardMandate = null, string $authenticationChannel = Constants::DEFAULT_AUTHENTICATION_CHANNEL)
    {
        $this->payment = $payment;

        $options = $this->getTerminalSelectionOptions();

        $input = [
            'payment'                   => $this->payment,
            'merchant'                  => $this->payment->merchant,
            'charge_account_merchant'   => $chargeAccountMerchant,
            'card_mandate'              => $cardMandate,
            'authentication_channel'    => $authenticationChannel,
        ];

        $terminalSelector = new Terminal\Selector($input, $options);

        $terminalsSelected = $terminalSelector->select();

        // Filter terminals during X onboarding
        if ($payment->getReceiverType() !== 'pos' and
            (isset($payment->receiver->source->balance) === true) and
            ($payment->receiver->source->balance->isTypeBanking() === true))
        {
            $terminalsSelected = $this->filterTerminalForRX($payment, $terminalsSelected);
        }

        if ($options->getMultiple() === false)
        {
            return [head($terminalsSelected)];
        }

        return $terminalsSelected;
    }

    public function getTerminalFromTerminalIds(array $terminalIds)
    {
        $terminals = $this->repo->terminal->findMany($terminalIds);

        //
        // We use `->values()` here since sortBy preserves the indexes and later,
        // when we get by index, we get the incorrect value. This happens because
        // the original index is not removed.
        //
        return $terminals->sortBy(function($terminal) use ($terminalIds)
        {
            return array_search($terminal->getId(), $terminalIds);
        })->values()->all();
    }

    public function setAuthenticationGateway(Payment\Entity $payment, array & $gatewayInput, string $authenticationChannel)
    {
        $this->payment = $payment;

        $input = [
            'payment'   => $this->payment,
            'merchant'  => $this->payment->merchant,
        ];

        $paymentAuthSelect = new Terminal\AuthSelector($input);

        $terminal = [];

        $isProduction = $this->app->environment(Environment::PRODUCTION);

        if ($isProduction === true || $this->shouldBVTRequestHitRouter($this->app['request']->header('X-RZP-TESTCASE-ID'))  || Environment::isEnvironmentPerf($this->app['env']))
        {
            try
            {
                $this->trace->info(
                    TraceCode::AUTH_SELECTION_VIA_SMART_ROUTING,
                    ['payment_id' => $payment->getId()]
                );

                $input = [
                    'payment'  => $this->payment,
                    'merchant' => $this->payment->merchant,
                ];

                $options = $this->getTerminalSelectionOptions();

                $terminalSelector = new Terminal\Selector($input, $options);

                $terminalAuthZ = $this->payment->terminal->toArray();

                $terminalsAuthZ = [$terminalAuthZ];

                $terminal = $terminalSelector->selectAuthenticationTerminal($terminalsAuthZ, $authenticationChannel);

                $this->trace->info(
                    TraceCode::AUTH_TERMINAL_SELECTED_VIA_SMART_ROUTING,
                    [
                        'payment_id' => $payment->getId(),
                        'terminal'   => $terminal,
                    ]
                );

                if ($terminal === null)
                {
                    $apiTerminal = $paymentAuthSelect->select();

                    $this->trace->info(
                        TraceCode::AUTH_TERMINAL_MISMATCH_VIA_SMART_ROUTING,
                        [
                            'payment_id' => $payment->getId(),
                            'terminal_selected_via_router'   => $terminal,
                            'terminal_selected_via_api'      => $apiTerminal,
                        ]
                    );

                    $terminal = $apiTerminal;
                }
            }
            catch (\Exception $e)
            {
                $terminal = $paymentAuthSelect->select();

                $this->trace->error(
                    TraceCode::SMART_ROUTING_AUTHN_REQUEST_FAILED,
                    [
                        'message' => 'Failed to send authentication data to smart routing',
                        'payment_id' => $payment->getId(),
                    ]
                );
            }
        }
        else
        {
            $terminal = $paymentAuthSelect->select();
        }

        // add default gateway_auth_version to v1 if not present
        if(!isset($terminal['gateway_auth_version']) or empty($terminal['gateway_auth_version']))
        {
            $terminal['gateway_auth_version'] = 'v1';
        }

        $this->trace->info(
                TraceCode::AUTH_SELECTION_FINAL_TERMINAL,
                ['terminal' => $terminal]
            );

        $gatewayInput['auth_type'] = $terminal['auth_type'];

        if (empty($terminal['authentication_gateway'] === false))
        {
            $gatewayInput['authenticate'] = [
              'gateway'   => $terminal['authentication_gateway'],
              'auth_type' => $terminal['gateway_auth_type'],
              'gateway_auth_version' => $terminal['gateway_auth_version']
            ];

            $payment->setAuthenticationGateway($gatewayInput['authenticate']['gateway']);
        }
    }

    public function selectAuthenticationGatewayForTerminals(Payment\Entity $payment, array $terminals)
    {
        $this->payment = $payment;

        $authenticationMap = [];

        foreach ($terminals as $terminal)
        {
            $payment->associateTerminal($terminal);

            $authenticationMap[$terminal->getId()] = [];

            $input = [
                'payment'   => $this->payment,
                'merchant'  => $this->payment->merchant,
            ];

            if (($payment->isMoto() === false) and
                ($payment->isSecondRecurring() === false))
            {
                try
                {
                    $paymentAuthSelect = new Terminal\AuthSelector($input);
                    $authenticationMap[$terminal->getId()] = $paymentAuthSelect->select();
                }
                catch(\Throwable $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::CRITICAL,
                        TraceCode::AUTH_SELECTION_FAILURE_V2,
                        ['payment_id' => $payment->getId()]
                    );
                }
            }
        }

        $payment->disassociateTerminal();

        return $authenticationMap;
    }


    public function getTerminalFromGatewayData(array $gatewayData = []): Terminal\Entity
    {
        $terminalId = $gatewayData[Payment\Entity::TERMINAL_ID];

        return $this->repo->terminal->find($terminalId);
    }

    public function getTerminalForBankTransfer(BankTransfer\Entity $bankTransfer, bool $log = false): Terminal\Entity
    {
        $gateway = Payment\Gateway::$bankTransferProviderGateway[$bankTransfer->getGateway()];

        $merchantId = $bankTransfer->getMerchantId();

        $terminalMerchantIds = [$merchantId, Account::SHARED_ACCOUNT];

        $terminals = $this->repo->terminal->getAllBankTransferTerminals($gateway, $terminalMerchantIds);

        return $this->getTerminalForBankAccount($gateway, $terminals, $bankTransfer->getPayeeAccount(), $log);
    }

    public function getTerminalForQrBankTransfer(Entity $bankAccount, $provider, bool $log = false): Terminal\Entity
    {
        $gateway = Payment\Gateway::$bankTransferProviderGateway[$provider];

        $terminalMerchantIds = [$bankAccount->getMerchantId(), Account::SHARED_ACCOUNT];

        $terminals = $this->repo->terminal->getAllBankTransferTerminals($gateway, $terminalMerchantIds);

        return $this->getTerminalForBankAccount($gateway, $terminals, $bankAccount->getAccountNumber(), $log);
    }

    private function getTerminalForBankAccount($gateway, $terminals, $virtualBankAccount, $log)
    {
        try
        {
            return $this->selectTerminalForBankAccount($terminals, $virtualBankAccount, $log);
        }
        catch (LogicException $ex)
        {
            //
            // When this error is thrown, the payee account is invalid and the bank
            // transfer is to be refunded. In order to refund successfully, we are just
            // returning a shared bank transfer terminal for that particular gateway.
            //
            if ($ex->getMessage() === 'No terminal found for bank transfer.')
            {
                $this->trace->traceException($ex);

                $terminal = $this->repo->terminal->findByGatewayAndTerminalData(
                    $gateway,
                    [
                        Terminal\Entity::MERCHANT_ID            => Account::SHARED_ACCOUNT,
                        Terminal\Entity::BANK_TRANSFER          => true,
                    ]
                );

                return $terminal;
            }

            throw $ex;
        }
    }

    /**
     * This function is used to fetch terminal for virtual VPA configs.
     * VPA terminals are created for shared merchant, but there can be dedicated terminal for merchant with custom vpa root.
     *
     * @param null $merchantId : Need to filter terminal if merchant has dedicated terminal for upi_transfer
     * @param null $gateway : When multiple terminals are enabled for upi_transfer, it can be used to filter terminals based on gateway.
     *
     * @return Terminal\Entity
     */
    public function getTerminalForUpiTransfer($merchantId = null, $gateway = null)
    {
        $merchantIds = array_filter([Account::SHARED_ACCOUNT, $merchantId]);

        $terminals = $this->repo
                          ->terminal
                          ->getByTypeAndMerchantIds(Terminal\Type::UPI_TRANSFER, $merchantIds);

        $this->trace->info(
            TraceCode::TERMINALS_FILTERED,
            [
                'terminal_ids' => $terminals->getIds(),
                'merchant_id'  => $merchantIds,
                'gateway'      => $gateway
            ]
        );

        if ($gateway !== null)
        {
            $terminals = $terminals->filter(function (Terminal\Entity $terminal) use ($gateway)
            {
                return ($terminal->getGateway() === $gateway);
            });
        }

        if ($merchantId !== null)
        {
            $terminals = $terminals->filter(function (Terminal\Entity $terminal) use ($merchantId)
            {
                return ($terminal->getMerchantId() === $merchantId);
            });
        }

        if($terminals->count() === 0)
        {
            $this->trace->error(
                TraceCode::VIRTUAL_ACCOUNT_VPA_CONFIG_NOT_FOUND
            );
        }

        return $terminals->last();
    }

    protected function selectTerminalForBankAccount(Base\PublicCollection $allTerminals, string $accountNumber, bool $log = false): Terminal\Entity
    {
        $matchingPrefixTerminals = $allTerminals->filter(function (Terminal\Entity $terminal) use ($accountNumber)
        {
            // We will filter the terminals which could possibly be used to make this account number.
            return $this->isTerminalValid($terminal, $accountNumber);
        });

        if ($log === true)
        {
            $this->trace->info(
                TraceCode::TERMINALS_FILTERED,
                ['matching_prefix_terminal_ids' => $matchingPrefixTerminals->getIds()]
            );
        }

         // Fallback Terminals are those terminals which are created with just Root
         // and are assigned to the Shared Merchant to get unexpected payments.
        $fallbackTerminals = new Base\PublicCollection();

        $selectedTerminals = $matchingPrefixTerminals->filter(function (Terminal\Entity $terminal) use ($fallbackTerminals)
        {
            if (($terminal->isShared() === true) and
                (empty($terminal->getGatewayMerchantId2()) === true))
            {
                $fallbackTerminals->push($terminal);

                return false;
            }

            return true;
        });

        if ($log === true)
        {
            $this->trace->info(
                TraceCode::TERMINALS_FILTERED,
                ['selected_terminal_ids' => $selectedTerminals->getIds()]
            );
        }

        if ($selectedTerminals->count() === 0)
        {
             // Count zero means none of the terminals could have created this bank account
             // and this is an unexpected bank transfer.
            if ($fallbackTerminals->count() === 1)
            {
                return $fallbackTerminals->first();
            }

            // This case will only happen if we get request for root that is not allotted
            // to us or we forgot to create the fallback terminal.
            throw new LogicException(
                'No terminal found for bank transfer.',
                null,
                [
                    'payee_account_prefix'      => substr($accountNumber, 0, 8),
                    'payee_account_descriptor'  => substr($accountNumber, 8, strlen($accountNumber)),
                ]
            );
        }

        if ($selectedTerminals->count() !== 1)
        {
             // If a Bank account matches with 2 terminals that means this can be created from
             // either of these and we should probably change the handle for 1 of the merchant
             // to avoid such future cases.
            $this->trace->error(
                TraceCode::BANK_TRANSFER_TERMINAL_COUNT_GREATER_THEN_ONE,
                [
                    'terminal_ids' => $selectedTerminals->getIds()
                ]
            );
        }

        return $selectedTerminals->first();
    }

    protected function isTerminalValid(Terminal\Entity $terminal, string $accountNumber)
    {
        $prefix = Provider::getRoot($terminal) . Provider::getHandle($terminal);

        return (substr($accountNumber, 0, strlen($prefix)) === $prefix);
    }

    protected function getTerminalSelectionOptions()
    {
        $options = new Terminal\Options;

        if (($this->payment->isMethodCardOrEmi() === true) or
            ($this->payment->isGooglePay() and in_array(Payment\Method::CARD, $this->payment->getGooglePayMethods())))
        {
            $failedTerminalIds = $this->getFailedTerminalIds();

            if (empty($failedTerminalIds) === false)
            {
                $this->trace->info(
                    TraceCode::TERMINAL_USED_BEFORE,
                    [
                        'failed_terminals'      => $failedTerminalIds,
                        'payment_id'            => $this->payment->getId(),
                    ]);

                $options->setFailedTerminals($failedTerminalIds);
            }
        }
        else if ($this->payment->isQrV2UpiPayment() === true)
        {
            $options->setMultiple(true);
        }
        else
        {
            $options->setMultiple(false);
        }

        return $options;
    }

    /**
     * Method to extract the terminals that failed. We first get the past payments
     * for a given payment flow, and get the terminals that were used as part of the
     * payment flow. We will sort the failed terminals as the last in the sorted terminals
     * to increase the payment efficacy
     * @return array
     */
    protected function getFailedTerminalIds()
    {
        $metadata = $this->payment->getMetadata();

        $orderId = $this->payment->getApiOrderId();

        $pastPayments = [];

        $failedTerminalIds = [];

        if ($orderId !== null)
        {
            $pastPayments = $this->repo->payment->getCreatedAndFailedPaymentsForOrder($orderId);
        }
        else if (isset($metadata[AnalyticsEntity::CHECKOUT_ID]) === true)
        {
            $checkoutId = $metadata[AnalyticsEntity::CHECKOUT_ID];

            $pastPayments = $this->repo->payment->getRecentMerchantPaymentsForCheckoutId($checkoutId);
        }

        foreach ($pastPayments as $pastPayment)
        {
            if (($pastPayment->hasNotBeenAuthorized()) and
                ($pastPayment->getTerminalId() !== null))
            {
                $failedTerminalIds[] = $pastPayment->getTerminalId();
            }
        }

        return array_values(array_unique($failedTerminalIds));
    }

    /**
     * Filter terminal during RX onboarding
     * This is used since we support multiple shared terminals on X now
     *
     * @param $payment
     * @param $terminals
     * @return array
     */
    protected function filterTerminalForRX($payment, $terminals)
    {
        // Get account number series prefix for merchant
        $seriesPrefix = Terminal\Core::getBankAccountSeriesPrefixForX($payment->merchant, $this->mode);

        $this->trace->info(
            TraceCode::TERMINALS_FILTERED,
            ['series_preifx' => $seriesPrefix]
        );

        foreach ($terminals as $terminal)
        {
            $gatewayMid = $terminal->getGatewayMerchantId();

            if (starts_with($gatewayMid, $seriesPrefix) === true)
            {
                return array($terminal);
            }
        }

        throw new LogicException(
            'No terminal found for RX',
            null,
            [
                'series_prefix' => $seriesPrefix,
            ]);
    }
}
