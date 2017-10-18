<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    protected $core;

    const DEFAULT_RECEIVER_TYPES = [
        Receiver::BANK_ACCOUNT,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATE_REQUEST, $input);

        $this->verifyMerchantIsLiveForLiveRequest();

        // @TODO: Change/Update this when more methods are added for Virtual Accounts
        $this->verifyBankTransferEnabled();

        $customer = $this->getCustomerIfGiven($input);

        $this->setDefaultReceiverTypesIfNeeded($input);

        $virtualAccount = $this->core->create($input, $this->merchant, $customer);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_CREATED,
            $virtualAccount->toArrayPublic()
        );

        return $virtualAccount->toArrayPublic();
    }

    public function fetch(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByIdAndMerchantWithRelations(
                                $id,
                                $this->merchant,
                                ['bankAccount']
                               );

        return $virtualAccount->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $virtualAccounts = $this->repo
                                ->virtual_account
                                ->fetch($input, $this->merchant->getId());

        return $virtualAccounts->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        $virtualAccount = $this->core->edit($virtualAccount, $input);

        return $virtualAccount->toArrayPublic();
    }

    public function fetchPayments(string $virtualAccountId)
    {
        $payments = $this->repo
                         ->payment
                         ->fetchBankTransferPaymentsByPublicVaIdAndMerchant(
                            $virtualAccountId,
                            $this->merchant
                            );

        return $payments->toArrayPublic();
    }

    public function refundExcessPayments()
    {
        $virtualAccounts = $this->repo
                                ->virtual_account
                                ->fetchExcessPaidVirtualAccounts();

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_EXCESS_REFUND,
            $virtualAccounts->toArrayPublic()
        );

        $success = $failure = 0;

        $failures = [];

        foreach ($virtualAccounts as $virtualAccount)
        {
            list($paymentToRefund, $amountToRefund) = $this->fetchPaymentToRefund($virtualAccount);

            try
            {
                $this->refundExcessPayment($paymentToRefund, $amountToRefund, $virtualAccount);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failure++;

                $failures[] = [
                    'payment_id'         => $paymentToRefund->getPublicId(),
                    'virtual_account_id' => $virtualAccount->getPublicId(),
                ];
            }
        }

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_EXCESS_REFUND,
            [
                'success'  => $success,
                'failure'  => $failure,
                'failures' => $failures,
            ]
        );

        return $virtualAccounts->toArrayPublic();
    }

    protected function fetchPaymentToRefund(Entity $virtualAccount)
    {
        $merchant = $virtualAccount->merchant;

        $payments = $this->repo
                         ->payment
                         ->fetchBankTransferPaymentsByPublicVaIdAndMerchant(
                            $virtualAccount->getPublicId(),
                            $merchant
                            );

        $paymentToRefund = $payments->first();

        $amountToRefund = $virtualAccount->getExcessAmount();

        if ($paymentToRefund->getAmount() < $amountToRefund)
        {
            throw new Exception\LogicException(
                'Last payment amount is less than VA excess',
                null,
                [
                    'payment_amount'    => $paymentToRefund->getAmount(),
                    'va_excess'         => $amountToRefund,
                    'payment_id'        => $paymentToRefund->getId(),
                    'va_id'             => $virtualAccount->getId(),
                ]);
        }

        return [$paymentToRefund, $amountToRefund];
    }

    protected function refundExcessPayment(
        Payment\Entity $payment,
        int $amount,
        Entity $virtualAccount)
    {
        $processor = $this->getNewProcessor($payment->merchant);

        $processor->refundPaymentViaMerchant(
                        $payment->getPublicId(),
                        [
                            'amount' => $amount,
                        ]);

        $virtualAccount->incrementAmountReversed($amount);

        $this->repo->saveOrFail($virtualAccount);
    }

    protected function getCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo
                             ->customer
                             ->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        return $customer;
    }

    protected function setDefaultReceiverTypesIfNeeded(array & $input)
    {
        if (empty($input[Entity::RECEIVER_TYPES]) === true)
        {
            $input[Entity::RECEIVER_TYPES] = self::DEFAULT_RECEIVER_TYPES;
        }

        if (is_array($input[Entity::RECEIVER_TYPES]) === false)
        {
            $input[Entity::RECEIVER_TYPES] = [$input[Entity::RECEIVER_TYPES]];
        }
    }

    protected function verifyMerchantIsLiveForLiveRequest()
    {
        // On live request, ensure that merchant isn't blocked temporarily
        if (($this->mode === Mode::LIVE) and
            ($this->merchant->isLive() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    protected function verifyBankTransferEnabled()
    {
        $merchantMethods = $this->getMethodsForMerchant($this->merchant);

        if (($merchantMethods === null) or
            ($merchantMethods->isBankTransferEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function getMethodsForMerchant(Merchant\Entity $merchant)
    {
        if ($merchant->hasRelation('methods') === false)
        {
            $methods = $this->repo->methods->getMethodsForMerchant($merchant);
        }

        return $merchant->methods;
    }

    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}
