<?php

namespace RZP\Models\Offer;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Checker extends Base\Core
{
    protected $offer;

    protected $order;

    protected $payment;

    protected $card;

    // Flag to toggle verbose logging, Initialised to false by default.
    protected $verbose;

    const CARD_USAGE = 'card_usage';

    /**
     * Properties used to check if offer is applicable on payment
     */
    const PROPERTIES_TO_CHECK = [
        Entity::PAYMENT_METHOD,
        Entity::IINS,
        Entity::ISSUER,
        Entity::PAYMENT_NETWORK,
        Entity::PAYMENT_METHOD_TYPE,
        self::CARD_USAGE,
    ];

    public function __construct(Entity $offer, bool $verbose = false)
    {
        parent::__construct();

        $this->offer = $offer;

        $this->verbose = $verbose;
    }

    public function checkOfferApplicableOnOrder(Order\Entity $order): bool
    {
        $this->order = $order;

        $offerActive = $this->offer->isActive();

        $validOfferPeriod = $this->checkOfferPeriod();

        return (($offerActive === true) and
                ($validOfferPeriod === true));
    }

    public function checkOfferApplicableOnPayment(Payment\Entity $payment): bool
    {
        $this->payment = $payment;

        $offerActive = $this->offer->isActive();

        $validOfferPeriod = $this->checkOfferPeriod();

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

        return (($offerActive === true) and
                ($validOfferPeriod === true) and
                ($checkResult === true));
    }

    protected function checkPaymentMethod(): bool
    {
        $paymentMethod = $this->payment->getMethod();

        $offerPaymentMethod = $this->offer->getPaymentMethod();

        if ($offerPaymentMethod !== null)
        {
            return ($offerPaymentMethod === $paymentMethod);
        }

        return true;
    }

    protected function checkPaymentMethodType(): bool
    {
        $offerPaymentMethodType = $this->offer->getPaymentMethodType();

        // Return true if no payment method type specified on offer
        // Means offer is valid on both credit/debit cards
        if (($offerPaymentMethodType === null) or
            ($this->payment->isMethodCardOrEmi() === false))
        {
            return true;
        }

        $card = $this->payment->card;

        $result = ($offerPaymentMethodType === $card->getType());

        $this->traceCheckResult(TraceCode::OFFER_CARD_TYPE_CHECK, [
            'result'            => $result,
            'offer_card_type'   => $offerPaymentMethodType,
            'payment_card_type' => $card->getType()
        ]);

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

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                $card = $this->payment->card;

                return ($offerIssuer === $card->getIssuer());

            case Payment\Method::NETBANKING:
                $bank = $this->payment->getBank();

                return ($offerIssuer === $bank);

            case Payment\Method::WALLET:
                $wallet = $this->payment->getWallet();

                return ($offerIssuer === $wallet);

            default:
                return false;
        }
    }

    protected function checkIins(): bool
    {
        $offerIins = $this->offer->getIins();

        if ((empty($offerIins) === true) or ($this->payment->isMethodCardOrEmi() === false))
        {
            return true;
        }

        $card = $this->payment->card;

        $result = false;

        foreach ($offerIins as $iin)
        {
            if (starts_with($card->getIin(), $iin) === true)
            {
                $result = true;

                break;
            }
        }

        $this->traceCheckResult(TraceCode::OFFER_CARD_IIN_CHECK, [
            'result'     => $result,
            'offer_iins' => $offerIins,
            'card_iin'   => $card->getIin()
        ]);

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

        return $result;
    }

    protected function checkOfferPeriod()
    {
        $now = Carbon::now()->getTimestamp();

        $result = (($now >= $this->offer->getStartsAt()) and
                    ($now <= $this->offer->getEndsAt()));

        $this->traceCheckResult(
            TraceCode::OFFER_PERIOD_CHECK,
            [
                'result' => $result
            ]);

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

        $cardVaultToken = $this->getCardVaultToken();

        $merchantId = $this->payment->merchant->getId();

        // Fetches all cards wihose own vault token or whose global cards have the
        // given vault token
        $cardIds = $this->repo->card->fetchWithVaultToken($cardVaultToken, $merchantId);

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
                'result'                   => $result,
                'payment_count_for_offers' => $paymentCountForOffers,
            ]);

        return $result;
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
                    return $paymentCount < $maxPaymentCount;
                }
            }
        }

        return true;
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
}
