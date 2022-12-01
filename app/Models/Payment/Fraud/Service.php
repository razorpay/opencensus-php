<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Models\Base;
use RZP\Models\Card\Network;
use RZP\Models\Currency;
use RZP\Models\Payment\Entity as Payment;

class Service extends Base\Service
{
    public function getFraudAttributes($input): array
    {
        (new Validator)->validateInput('get_attributes', $input);

        $paymentId = Payment::verifyIdAndSilentlyStripSign($input[Entity::PAYMENT_ID]);

        $payment = $this->repo->payment->findOrFailPublic($paymentId);

        $fraudTypes = Constants::DEFAULT_FRAUD_TYPES;

        $fraudSubTypes = [];

        // For all other payments except via MasterCard network, Fraud types similar to Visa need to be shown
        if($payment->hasCard() === true and $payment->card->getNetwork() === Network::getFullName(Network::MC))
        {
            $fraudTypes = Constants::MASTERCARD_FRAUD_TYPES;

            $fraudSubTypes = Constants::MASTERCARD_FRAUD_SUB_TYPES;
        }

        return [
            Constants::TYPES        =>  $fraudTypes,
            Constants::SUB_TYPES    =>  $fraudSubTypes,
            Constants::REPORTED_BY  =>  Constants::REPORTED_BY_VALUES,
        ];
    }

    public function savePaymentFraud($input)
    {
        (new Validator)->validateInput('create_dashboard', $input);

        $input[Entity::PAYMENT_ID] = Payment::verifyIdAndSilentlyStripSign($input[Entity::PAYMENT_ID]);

        $payment = $this->repo->payment->findOrFailPublic($input[Entity::PAYMENT_ID]);

        $baseCurrency = $payment->merchant->getCurrency();

        $input[Entity::PAYMENT_ID] = $payment->getID();

        $input[Entity::CHARGEBACK_CODE] = ($input[Constants::HAS_CHARGEBACK] === '1')
            ? Constants::INTERNAL_CHARGEBACK_CODE : null;

        $input[Entity::IS_ACCOUNT_CLOSED] = (int) $input[Entity::IS_ACCOUNT_CLOSED];

        $input[Entity::AMOUNT] *= 100;

        $input[Entity::BASE_AMOUNT] = (new Currency\Core)->getBaseAmount(
            $input[Entity::AMOUNT],
            $input[Entity::CURRENCY],
            $baseCurrency);

        $skipMerchantEmail = $input[Constants::SKIP_MERCHANT_EMAIL];

        unset($input[Constants::HAS_CHARGEBACK]);
        unset($input[Constants::SKIP_MERCHANT_EMAIL]);

        [$isEntityCreated, $fraudEntity] = (new Core)->createOrUpdateFraudEntity($input);

        if ((int) $skipMerchantEmail === 0)
        {
            (new Core())->notifyFraud($fraudEntity);
        }

        return $fraudEntity;
    }
}
