<?php

namespace RZP\Models\Offer;

use App;
use Carbon\Carbon;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base;
use RZP\Models\Card\CobrandingPartner;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Emi;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Card;
use RZP\Models\Customer\Token;

class Checker extends Base\Core
{
    protected $offer;

    protected $order;

    protected $payment;

    protected $card;

    protected $isDummyPayment;

    // Flag to toggle verbose logging, Initialised to false by default.
    protected $verbose;

    const CARD_USAGE = 'card_usage';
    const MIN_AMOUNT_CARDLESS_EMI = 'min_amount_cardless_emi';

    /**
     * Properties used to check if offer is applicable on payment
     */
    const PROPERTIES_TO_CHECK = [
        Entity::PAYMENT_METHOD,
        Entity::IINS,
        Entity::ISSUER,
        Entity::INTERNATIONAL,
        Entity::PAYMENT_NETWORK,
        Entity::PAYMENT_METHOD_TYPE,
        Entity::EMI_DURATIONS,
        self::CARD_USAGE,
        Entity::MIN_AMOUNT,
        Entity::MAX_ORDER_AMOUNT,
        self::MIN_AMOUNT_CARDLESS_EMI,
    ];

    public function __construct(Entity $offer, bool $verbose = false)
    {
        parent::__construct();

        $this->offer = $offer;

        $this->verbose = $verbose;
    }

    public function checkApplicabilityOnOrder(Order\Entity $order): bool
    {
        $this->order = $order;

        $offerActive = $this->offer->isActive();

        $validOfferPeriod = $this->checkOfferPeriod();

        return (($offerActive === true) and
                ($validOfferPeriod === true));
    }

    public function checkValidityOnOrder(Order\Entity $order): bool
    {
        $this->order = $order;

        $validMinOrderAmount = $this->checkMinAmount();

        if($validMinOrderAmount === false)
        {
            return false;
        }

        $validMaxOrderAmount = $this->checkMaxOrderAmount();

        if($validMaxOrderAmount === false)
        {
            return false;
        }

        $isMaxOfferUsageExceeded = true;

        if($this->offer->getMaxOfferUsage() !== null)
        {
            $isMaxOfferUsageExceeded = $this->offer->getCurrentOfferUsage() < $this->offer->getMaxOfferUsage();
        }

        if($isMaxOfferUsageExceeded === false)
        {
            return false;
        }

        if($this->checkApplicabilityOnOrder($order) === false)
        {
            return false;
        }

        return true;
    }

    public function checkApplicabilityOfOfferMerchant(): bool
    {
        $offerActive = $this->offer->isActive();

        $validOfferPeriod = $this->checkOfferPeriod();

        return (($offerActive === true) and
            ($validOfferPeriod === true));
    }

    public function checkOfferValidityOnMerchant(): bool
    {
        $isMaxOfferUsageExceeded = true;

        if($this->offer->getMaxOfferUsage() !== null)
        {
            $isMaxOfferUsageExceeded = $this->offer->getCurrentOfferUsage() < $this->offer->getMaxOfferUsage();
        }

        if($isMaxOfferUsageExceeded === false)
        {
            return false;
        }

        if($this->checkApplicabilityOfOfferMerchant() === false)
        {
            return false;
        }

        return true;
    }
    public function checkApplicabilityForPayment(Payment\Entity $payment, Order\Entity $order): bool
    {
        $this->payment = $payment;
        $this->isDummyPayment = false;
        $this->order = $order;

        $isCurrentOfferUsageAvailable = $this->checkMaxOfferUsage();

        if($isCurrentOfferUsageAvailable === false)
        {
            return false;
        }

        return $this->checkOfferIsValidOrNot();
    }

    public function checkApplicabilityForPaymentBeforeCheckout(Payment\Entity $payment, Order\Entity $order): bool
    {
        $this->payment = $payment;
        $this->isDummyPayment = true;
        $this->order = $order;

        if(($this->offer->getMaxOfferUsage() !== NULL) and
            ($this->offer->getCurrentOfferUsage() >= $this->offer->getMaxOfferUsage()))
        {
            $this->traceCheckResult(
                TraceCode::OFFER_USAGE_CHECK,
                [
                    'result' => 'false',
                    'max_count_for_offer' => $this->offer->getMaxOfferUsage(),
                    'current_offer_usage' => $this->offer->getCurrentOfferUsage(),
                ]);

            return false;
        }

        return $this->checkOfferIsValidOrNot();
    }

    private function checkOfferIsValidOrNot()
    {
        $offerActive = $this->checkOfferActive();

        if ($offerActive === false)
        {
            return false;
        }

        $validOfferPeriod = $this->checkOfferPeriod();

        if($validOfferPeriod === false)
        {
            return false;
        }
        $checkResult = false;

        foreach (self::PROPERTIES_TO_CHECK as $property)
        {
            $checkMethod = 'check' . studly_case($property);

            $checkResult = $this->$checkMethod();

            if ($checkResult === false)
            {
                break;
            }
        }

        return $checkResult;
    }


    protected function checkOfferActive(): bool
    {
        $offerActive = $this->offer->isActive();

        if($offerActive === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_NOT_ACTIVE);
        }

        return $offerActive;
    }

    protected function checkPaymentMethod(): bool
    {
        $paymentMethod = $this->payment->getMethod();

        $offerPaymentMethod = $this->offer->getPaymentMethod();

        if($offerPaymentMethod === null)
        {
            return true;
        }

        $result = ($offerPaymentMethod === $paymentMethod);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_PAYMENT_METHOD_NOT_AVAILABLE);
        }

        return $result;
    }

    protected function checkPaymentMethodType(): bool
    {
        $offerPaymentMethodType = $this->offer->getPaymentMethodType();

        //If payment method type for offer is null then set method type as credit
        if(($this->offer->getPaymentMethod() === Payment\Method::EMI) and $offerPaymentMethodType === null) {
            $offerPaymentMethodType = Emi\Type::CREDIT;
        }

        // Return true if no payment method type specified on offer
        // Means offer is valid on both credit/debit cards
        if (($offerPaymentMethodType === null) or
            ($this->payment->isMethodCardOrEmi() === false))
        {
            return true;
        }

        $card = $this->payment->card;

        $result = (strtolower($offerPaymentMethodType) === strtolower($card->getType()));

        $this->traceCheckResult(TraceCode::OFFER_CARD_TYPE_CHECK, [
            'result'            => $result,
            'offer_card_type'   => $offerPaymentMethodType,
            'payment_card_type' => $card->getType()
        ]);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_CARD_TYPE_DOES_NOT_MATCH);
        }

        return $result;
    }

    protected function checkPaymentNetwork(): bool
    {
        $offerPaymentNetwork = $this->offer->getPaymentNetwork();

        // Return true if payment network is null for offer
        // Offer is valid across all card networks
        if (($offerPaymentNetwork === null) or ($this->payment->isMethodCardOrEmi() === false))
        {
            return true;
        }

        $card = $this->payment->card;

        $result = ($offerPaymentNetwork === $card->getNetworkCode());

        $this->traceCheckResult(TraceCode::OFFER_CARD_NETWORK_CHECK, [
            'result'               => $result,
            'offer_card_network'   => $offerPaymentNetwork,
            'payment_card_network' => $card->getNetworkCode()
        ]);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_PAYMENT_NETWORK_NOT_AVAILABLE);
        }

        return $result;
    }

    protected function checkIssuer(): bool
    {
        $offerIssuer = $this->offer->getIssuer();

        if ($offerIssuer === null)
        {
            return true;
        }

        $paymentMethod = $this->payment->getMethod();

        $result = false;

        $card = $this->payment->card;

        if($card !== null)
        {
            $cardActualIin = $this->fetchCardIIN();

            $iinEntity = $this->repo->iin->find($cardActualIin);

            $coBrandingPartner = $iinEntity->getCobrandingPartner();

            $offerIins = $this->offer->getIins();

            // If an offer has both iins and issuer on one-card, then we check both
            // If only issuer based offer is applied on one-card, we fail the offer

            if ($coBrandingPartner === CobrandingPartner::ONECARD and empty($offerIins) === true) {
                return $result;
            }
        }

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $card = $this->payment->card;

                $result = ($offerIssuer === $card->getIssuer());

                break;

            case Payment\Method::NETBANKING:
                $bank = $this->payment->getBank();

                $result = ($offerIssuer === $bank);

                break;

            case Payment\Method::WALLET:
                $wallet = $this->payment->getWallet();

                $result = ($offerIssuer === $wallet);

                break;

            default:
                $result;
                $this->traceCheckResult(
                    TraceCode::OFFER_CARD_ISSUER_CHECK,
                    [
                        'result'                   => $result,
                        'offer_issuer'             => $offerIssuer,
                        'payment_method'           => $paymentMethod,
                    ]);
        }
        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_NOT_APPLICABLE_ON_ISSUER);
        }

        return $result;
    }

    protected function checkEmiDurations()
    {
        $emiDurations = $this->offer->getEmiDurations();

        if (empty($emiDurations) === true)
        {
            return true;
        }

        $emiDuration = $this->payment->emiPlan->getDuration();

        $result = (in_array($emiDuration, $emiDurations, true) === true);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_EMI_DURATION_NOT_SAME);
        }

        return $result;
    }

    protected function checkInternational(): bool
    {
        $isInternational = $this->offer->isInternational();

        if (($isInternational === null) or ($this->payment->isMethodCardOrEmi() === false))
        {
            return true;
        }

        $card = $this->payment->card;

        $result = ($card->isInternational() === $isInternational);

        $this->traceCheckResult(TraceCode::OFFER_CARD_INTERNATIONAL_CHECK, [
            'result'            => $result,
            'offer_international' => $isInternational,
            'international'     => $card->isInternational(),
        ]);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_CARD_INTERNATIONAL);
        }

        return $result;
    }

    protected function checkIins(): bool
    {
        $offerIins = $this->offer->getIins();

        if ((empty($offerIins) === true) or ($this->payment->isMethodCardOrEmi() === false))
        {
            return true;
        }

       $cardActualIin = $this->fetchCardIIN();

        $result = false;

        foreach ($offerIins as $iin)
        {
            if (starts_with($cardActualIin, $iin) === true)
            {
                $result = true;

                break;
            }
        }

        $this->traceCheckResult(TraceCode::OFFER_CARD_IIN_CHECK, [
            'result'     => $result,
            'offer_iins' => $offerIins
        ]);

        if ($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_IINS_DOES_NOT_MATCH);
        }

        return $result;
    }

    protected function checkWallet()
    {
        $offerPaymentNetwork = $this->offer->getPaymentNetwork();

        // Return true if payment network is null for offer
        // Offer is valid across all wallets
        if ($offerPaymentNetwork === null)
        {
            return true;
        }

        $result = ($offerPaymentNetwork === $this->payment->getWallet());

        $this->traceCheckResult(TraceCode::OFFER_WALLET_CHECK, [
            'result'         => $result,
            'offer_wallet'   => $offerPaymentNetwork,
            'payment_wallet' => $this->payment->getWallet()
        ]);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_WALLET_NOT_SAME);
        }

        return $result;
    }

    protected function checkOfferPeriod()
    {
        $result = $this->offer->isPeriodActive();

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_PERIOD_NOT_ACTIVE);
        }

        return $result;
    }

    protected function checkCardUsage(): bool
    {
        // Skip card usage check if payment method is not card or emi
        // or if the max payment count is not present
        if (($this->payment->isMethodCardOrEmi() === false) or
            (empty($this->offer->getMaxPaymentCount()) === true))
        {
            return true;
        }
        // Offer max usage will only be applicable for cards network which have exposed PAR api
        if ((new Card\Core())->checkIfFetchingParApplicable($this->payment->card->getNetwork()) === false)
        {
            $this->traceCheckResult(
                TraceCode::OFFER_CARD_USAGE_CHECK,
                [
                    'network' => $this->payment->card->getNetwork(),
                    'message' => 'Max offer usage per card is not applicable on this card'
                ]);
            return false;
        }

        $providerReferenceId = $this->payment->card->getProviderReferenceId();

        // If provider_reference_id is null, then we will call the par api to get the provider_reference_id
        if (empty($providerReferenceId) === true)
        {
            $providerReferenceId = $this->getParValue();
        }

        // If provider_reference_id is not null, we will validate the max usage on the current card
        if (empty($providerReferenceId) === false)
        {
            $merchantId = $this->payment->merchant->getId();

            $cardIds = $this->repo->card->fetchCardIdsWithProviderReferenceId($providerReferenceId, $merchantId);

            // Gets linked offer ids to get payment count if any and
            // appends current offer's id with it
            $offerIds = $this->offer->getLinkedOfferIds();
            $offerIds[] = $this->offer->getId();

            // Gets the number of successfully captured payments which have been paid
            // with the cardIds fetched above and whose associated order has the above
            // offerIds applied on them
            $paymentCountForOffers = $this->repo
                ->payment
                ->getPaymentCountForCardIdsAndOfferIds($cardIds, $offerIds);

            $result = $this->checkPaymentCountForOffer($paymentCountForOffers);

            $this->traceCheckResult(
                TraceCode::OFFER_CARD_USAGE_CHECK,
                [
                    'result' => $result,
                    'payment_count_for_offers' => $paymentCountForOffers,
                ]);

            return $result;
        }
        return false;
    }

    protected function getParValue() {

        $card = $this->payment->card;
        $vaultToken = $card->getVaultToken();
        $cardNumber = (new Card\CardVault)->getCardNumber($vaultToken);

        $cardInput = (new Token\Core())->buildCardInputForPar($cardNumber, $card);

        // Fetches par value for given card number
        list($network, $data) = (new Token\Core())->fetchParValue($cardInput, true);
        $providerReferenceId = $data["fingerprint"];


        $card->setProviderReferenceId($providerReferenceId);

        // For dummy payment we will not persist the card entity
        if ($this->isDummyPayment === false)
        {
            $this->repo->card->saveOrFail($card);
        }
        return $providerReferenceId;
    }

    protected function checkPaymentCountForOffer(array $paymentCountForOffers): bool
    {
        if (empty($paymentCountForOffers) === false)
        {
            foreach ($paymentCountForOffers as $offerId => $paymentCount)
            {
                // If a payment has already been made against a linked offer Id fail
                // the check
                if ($offerId !== $this->offer->getId())
                {
                    return false;
                }

                // If payment has been made against current offer id, check
                // if payment count exceeds max count
                if ($offerId === $this->offer->getId())
                {
                    $maxPaymentCount = $this->offer->getMaxPaymentCount();

                    // We are using < operator as paymentCount tracks the number of times payment
                    // has been made against the offer before current payment
                    $result = $paymentCount < $maxPaymentCount;

                    if($result === false)
                    {
                        $this->offer
                             ->setErrorMessage(PublicErrorDescription::OFFER_MAX_CARD_USAGE_LIMIT_EXCEEDED);
                    }

                    return $result;
                }
            }
        }

        return true;
    }


    //Checks the number of successfully created payments have been made with that offer, should
    //not exceed the max offer usage count
    protected function checkMaxOfferUsage(): bool
    {
        if($this->offer->getMaxOfferUsage() === NULL)
        {
            return true;
        }

        $core = new Core();

        $updatedOffer = $core->lockIncrementCurrentOfferUsage($this->offer);

        $result = $updatedOffer->getCurrentOfferUsage() <= $this->offer->getMaxOfferUsage();

        $this->traceCheckResult(
                TraceCode::OFFER_USAGE_CHECK,
                [
                    'result' => $result,
                    'max_count_for_offer' => $this->offer->getMaxOfferUsage(),
                    'current_offer_usage' => $this->offer->getCurrentOfferUsage(),
                ]);

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_MAX_OFFER_LIMIT_EXCEEDED);
        }

        return $result;
    }


    protected function getCardVaultToken(): string
    {
        if ($this->payment->card->hasGlobalCard() === true)
        {
            return $this->payment->card->globalCard->getVaultToken();
        }

        return $this->payment->card->getVaultToken();
    }

    protected function traceCheckResult(string $traceCode, array $data)
    {
        if ($this->verbose === true)
        {
            $this->trace->debug($traceCode, $data);
        }
    }

    protected function checkMinAmount(): bool
    {
        if($this->offer->getMinAmount() === null)
        {
            return true;
        }

        $result = $this->order->getAmount() >= $this->offer->getMinAmount();

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_ORDER_AMOUNT_LESS_OFFER_MIN_AMOUNT);
        }

        $calculator = new Calculator($this->offer);

        try
        {
            $calculator->calculateDiscountedAmount($this->order->getAmount(), null);
        }
        catch (LogicException $e)
        {
            return false;
        }

        return $result;
    }

    protected function checkMaxOrderAmount(): bool
    {
        if($this->offer->getMaxOrderAmount()=== null)
        {
            return true;
        }

        $result = $this->order->getAmount() <= $this->offer->getMaxOrderAmount();

        if($result === false)
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_ORDER_AMOUNT_GREATER_OFFER_MAX_AMOUNT);
        }

        return $result;
    }

    protected function checkMinAmountCardlessEmi(): bool
    {
        $issuer = $this->payment->getIssuer();
        $orderAmount = $this->order->getAmount();

        // Order amount should be greater or equals than minimum transaction amount of cardless emi provider
        if (($this->payment->getMethod() === Payment\Method::CARDLESS_EMI) and
            (CardlessEmi::exists($issuer) === true) and
            ($orderAmount < CardlessEmi::MIN_AMOUNTS[$issuer]))
        {
            $this->offer->setErrorMessage(PublicErrorDescription::OFFER_ORDER_AMOUNT_LESS_PROVIDER_MIN_TRANSACTION_AMOUNT);
            return false;
        }

        return true;
    }

    protected function fetchCardIIN(): string
    {
        $card = $this->payment->card;

        $cardActualIin = null;

        $cardTokenIin = $card->getTokenIin();

        if (empty($cardTokenIin) === false)
        {
            $cardActualIin = (string)Card\IIN\IIN::getTransactingIinforRange($cardTokenIin);

            if (empty($cardActualIin) === true)
            {
                $this->trace->info(TraceCode::BIN_MAPPING_FOR_TOKEN_NOT_AVAILABLE);
            }
        }
        // not adding this in else condition because this check is needed even for tokenised cards flow after mapping fails.
        if (empty($cardActualIin) === true)
        {
            $cardActualIin = $card->getIin();
        }

        return $cardActualIin;
    }
}
