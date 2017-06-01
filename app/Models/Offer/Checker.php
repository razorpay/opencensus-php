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

    public function __construct(Entity $offer, bool $verbose = false)
    {
        parent::__construct();

        $this->offer = $offer;

        $this->verbose = $verbose;
    }

    public function checkOfferApplicableOnOrder(Order\Entity $order)
    {
        $this->order = $order;

        $offerActive = $this->offer->isActive();

        $validOfferPeriod = $this->checkOfferPeriod();

        return (($offerActive === true) and
                ($validOfferPeriod === true));
    }

    public function checkOfferApplicableOnPayment(Payment\Entity $payment)
    {
        $this->payment = $payment;

        $validPaymentMethod = $this->checkPaymentMethod();

        $offerActive = $this->offer->isActive();

        $validOfferPeriod = $this->checkOfferPeriod();

        $validCardUsage = $this->checkCardUsage();

        return (($validPaymentMethod === true) and
                ($offerActive === true) and
                ($validOfferPeriod === true) and
                ($validCardUsage === true));
    }

    protected function checkPaymentMethod()
    {
        $paymentMethod = $this->payment->getMethod();

        $checkerFunction = 'check' . studly_case($paymentMethod);

        return $this->$checkerFunction();
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

    protected function checkNetbanking()
    {
        $offerPaymentNetwork = $this->offer->getPaymentNetwork();

        // Return true if payment network is null for offer
        // Offer is valid across all banks
        if ($offerPaymentNetwork === null)
        {
            return true;
        }

        $result = ($offerPaymentNetwork === $this->payment->getBank());

        $this->traceCheckResult(TraceCode::OFFER_NETBANKING_CHECK, [
            'result'         => $result,
            'offer_bank'     => $offerPaymentNetwork,
            'payment_bank'   => $this->payment->getBank()
        ]);
    }

    /**
     * For emi we are currently just validating against the card
     * TBD if any other validations are required
     */
    protected function checkEmi()
    {
        return $this->checkCard();
    }

    protected function checkCard()
    {
        $this->card = $this->payment->card;

        $iins = $this->offer->getIins();

        if (empty($iins) === false)
        {
            $result = (in_array($this->card->getIin(), $iins, true) === true);

            $this->traceCheckResult(TraceCode::OFFER_CARD_IIN_CHECK, [
                'iin'        => $this->card->getIin(),
                'result'     => $result,
            ]);

            return $result;
        }

        $validCardType = $this->checkCardType();

        $validCardNetwork = $this->checkCardNetwork();

        $validCardIssuer = $this->checkCardIssuer();

        return (($validCardType === true) and
                ($validCardNetwork === true) and
                ($validCardIssuer === true));
    }

    protected function checkCardType()
    {
        $offerPaymentMethodType = $this->offer->getPaymentMethodType();

        // Return true if no payment method type specified on offer
        // Means offer is valid on both credit/debit cards
        if ($offerPaymentMethodType === null)
        {
            return true;
        }

        $result = ($offerPaymentMethodType === $this->card->getType());

        $this->traceCheckResult(TraceCode::OFFER_CARD_TYPE_CHECK, [
            'result'            => $result,
            'offer_card_type'   => $offerPaymentMethodType,
            'payment_card_type' => $this->card->getType()
        ]);

        return $result;
    }

    protected function checkCardNetwork()
    {
        $offerPaymentNetwork = $this->offer->getPaymentNetwork();

        // Return true if payment network is null for offer
        // Offer is valid across all card networks
        if ($offerPaymentNetwork === null)
        {
            return true;
        }

        $result = ($offerPaymentNetwork === $this->card->getNetworkCode());

        $this->traceCheckResult(TraceCode::OFFER_CARD_NETWORK_CHECK, [
            'result'               => $result,
            'offer_card_network'   => $offerPaymentNetwork,
            'payment_card_network' => $this->card->getNetworkCode()
        ]);

        return $result;
    }

    protected function checkCardIssuer()
    {
        $offerCardIssuer = $this->offer->getIssuer();

        if ($offerCardIssuer === null)
        {
            return true;
        }

        $result = ($offerCardIssuer === $this->card->getIssuer());

        $this->traceCheckResult(TraceCode::OFFER_CARD_ISSUER_CHECK, [
            'result'              => $result,
            'offer_card_issuer'   => $offerCardIssuer,
            'payment_card_issuer' => $this->card->getIssuer()
        ]);

        return $result;
    }

    protected function checkPaymentAmount()
    {
        $result = ($this->payment->getAmount() >= $this->offer->getMinAmount());

        $this->traceCheckResult(TraceCode::OFFER_PAYMENT_AMOUNT_CHECK, [
                'result'           => $result,
                'offer_min_amount' => $this->offer->getMinAmount(),
                'payment_amount'   => $this->payment->getAmount()
        ]);

        return $result;
    }

    protected function checkOrderAmount()
    {
        $result = ($this->order->getAmount() >= $this->offer->getMinAmount());

        $this->traceCheckResult(TraceCode::OFFER_ORDER_AMOUNT_CHECK, [
                'result'           => $result,
                'offer_min_amount' => $this->offer->getMinAmount(),
                'order_amount'     => $this->order->getAmount()
        ]);

        return $result;
    }

    protected function checkOfferPeriod()
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $result = (($now >= $this->offer->getStartsAt()) and
                    ($now <= $this->offer->getEndsAt()));

        $this->traceCheckResult(
            TraceCode::OFFER_PERIOD_CHECK,
            [
                'result' => $result
            ]);

        return $result;
    }

    protected function checkCardUsage()
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
