<?php

namespace RZP\Models\Order;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Bank\BankCodes;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants as FeatureConstants;

class Core extends Base\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param boolean         $partialPayment
     *
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant, bool $partialPayment = false)
    {
        $this->trace->info(
            TraceCode::ORDER_CREATE_REQUEST,
            $input
        );

        $order = new Entity;

        // Needs to be associated first cause merchant entity is required
        // in orders create validators.
        $order->merchant()->associate($merchant);

        $order->build($input);

        $order->generateId();

        $this->validateReceiptUniqueness($order);

        if ($partialPayment === true)
        {
            $order->allowPartialPayment();
        }

        $order = $this->repo->transaction(function() use ($order, $input)
        {
            $ba = $this->createAndAssociateBankAccount($order, $input);

            $order->getValidator()->validateMerchantSpecificData();

            if (empty($ba) === false)
            {
                $order->setAccountNumber($ba->getAccountNumber());

                $order->setPayerName($ba->getBeneficiaryName());
            }

            $this->associateOffers($order, $input);

            $this->repo->saveOrFail($order);

            return $order;
        });

        $this->trace->info(
            TraceCode::ORDER_CREATED,
            ['order_id' => $order->getId()]
        );

        return $order;
    }

    public function getInputWithoutExtraParams(array $input)
    {
        $newInput = $input;

        foreach (ExtraParams::allExtraParams as $extraParam)
        {
            if (array_key_exists($extraParam, $newInput) === true)
            {
                unset($newInput[$extraParam]);
            }
        }

        return $newInput;
    }

    protected function createAndAssociateBankAccount(Entity $order, array $input)
    {
        if (isset($input[Entity::BANK_ACCOUNT]) === false)
        {
            return;
        }

        return (new BankAccount\Core)->createBankAccountForSource(
            $input[Entity::BANK_ACCOUNT],
            $order->merchant,
            $order,
            'addTpvBankAccount');
    }

    protected function associateOffers(Entity $order, array $input)
    {
        if (isset($input[Entity::OFFERS]) === false)
        {
            return;
        }

        foreach (array_unique($input[Entity::OFFERS]) as $offerId)
        {
            $this->validateAndAssociateOffer($order, $offerId);
        }
    }

    protected function validateAndAssociateOffer(Entity $order, string $offerId)
    {
        $offer = (new Offer\Core)->fetchAndValidateOfferForOrder($offerId, $order);

        // Creates row in entity_offers table
        $order->associateOffer($offer);

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_ORDER,
            [
                'offer_id' => $offerId,
                'order_id' => $order->getId()
            ]);
    }

    /**
     * Returns formatted data of order to be used by checkout.
     * Includes:
     * - Amount fields
     * - TPV data
     *
     * @param Entity          $order
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function getFormattedDataForCheckout(
        Entity $order,
        Merchant\Entity $merchant): array
    {
        if ($order->getStatus() === Status::PAID)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
                null,
                [
                    'order_id' => $order->getId(),
                ]);
        }

        $data = [
            Entity::PARTIAL_PAYMENT          => $order->isPartialPaymentAllowed(),
            Entity::AMOUNT                   => $order->getAmount(),
            Entity::CURRENCY                 => $order->getCurrency(),
            Entity::AMOUNT_PAID              => $order->getAmountPaid(),
            Entity::AMOUNT_DUE               => $order->getAmountDue(),
            Entity::FIRST_PAYMENT_MIN_AMOUNT => $order->getFirstPaymentMinAmount(),
        ];

        $orderMethod = $order->getMethod();

        if ($merchant->isTPVRequired() === true)
        {
            // TODO: Change this after creating bank account entities for all the previous TPV orders
            $accountNumber = empty($order->bankAccount) === true ? $order->getAccountNumber() : $order->bankAccount->getAccountNumber();

            $data += [
                Entity::BANK           => $order->getBank(),
                Entity::ACCOUNT_NUMBER => $this->getMaskedAccountNumber($accountNumber),
            ];

            if ($orderMethod !== null)
            {
                $data += [Entity::METHOD => $orderMethod];
            }
        }
        else if ($order->getBank() !== null)
        {
            $data += [
                Entity::BANK           => $order->getBank(),
            ];
        }

        $tokenRegistration = $order->getTokenRegistration();

        if ($tokenRegistration !== null)
        {
            if ($orderMethod !== null)
            {
                $data += [Entity::METHOD => $orderMethod];
            }

            if ( ($tokenRegistration->getEntityType() === Entity::BANK_ACCOUNT) === true )
            {
                $bankAccount = $tokenRegistration->bankAccount;

                $bankCode = $bankAccount->getBankCode();

                $data[Entity::BANK] = $bankCode;

                $data[Entity::BANK_ACCOUNT] = $bankAccount->getDataForCheckout();

                $data[Entity::AUTH_TYPE] = $tokenRegistration->getAuthType();
            }
        }

        return $data;
    }

    public function getMaskedAccountNumber($accountNumber)
    {
        $accountNumberLength = strlen($accountNumber);

        $last2Digits = substr($accountNumber, -2);

        $formattedNumber = str_repeat('X', $accountNumberLength - 2) . $last2Digits;

        return $formattedNumber;
    }

    public function getAccountForRefund(Entity $order)
    {
        $payerAccount = $order->bankAccount;

        // TODO: Change this after creating bank account entities for all the previous TPV orders
        if (empty($payerAccount) === true)
        {
            $ifscCode = BankCodes::getIfscForBankCode($order->getBank());

            $beneficiaryName = $order->getPayerName();

            $input[BankAccount\Entity::IFSC_CODE] = $ifscCode;
            $input[BankAccount\Entity::ACCOUNT_NUMBER] = $order->getAccountNumber();
            $input[BankAccount\Entity::BENEFICIARY_NAME] = ($beneficiaryName === null) ? '' : $beneficiaryName;
        }
        else
        {
            $beneficiaryName = $payerAccount->getBeneficiaryName();

            $input = [
                BankAccount\Entity::IFSC_CODE        => $payerAccount->getIfscCode(),
                BankAccount\Entity::ACCOUNT_NUMBER   => $payerAccount->getAccountNumber(),
                BankAccount\Entity::BENEFICIARY_NAME => ($beneficiaryName === null) ? '' : $beneficiaryName,
            ];
        }

        return $input;
    }

    /**
     * Validates the uniqueness of the receipt for featured merchants. The uniqueness here, is within the orders of that
     * particular merchant and not across all the merchants.
     *
     * @param Entity $order
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateReceiptUniqueness(Entity $order)
    {
        $merchant = $order->merchant;

        if ($merchant->isFeatureEnabled(FeatureConstants::ORDER_RECEIPT_UNIQUE) === false)
        {
            return;
        }

        $receipt = $order->getReceipt();

        if ($receipt === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_REQUIRED,
                Entity::RECEIPT);
        }

        $params = [Entity::RECEIPT => $receipt];

        $duplicateOrders = $this->repo->order->fetch($params, $merchant->getId());

        if (count($duplicateOrders) > 0)
        {
            $duplicateOrderIds = $duplicateOrders->pluck(Entity::ID)->all();

            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_NOT_UNIQUE,
                ['order_ids' => $duplicateOrderIds]);
        }
    }
}
