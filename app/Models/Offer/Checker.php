<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use RZP\Models\Order;
use RZP\Models\Payment;

class Checker
{
    protected $offer;

    protected $order;

    public function __construct(Entity $offer)
    {
        $this->offer = $offer;
    }

    public function checkOfferApplicableOnOrder(Order\Entity $order)
    {
        $this->order = $order;

        $validOrderAmount = $this->checkOrderAmount();

        $offerActiveAndNotExpired = $this->checkOfferActiveAndNotExpired();

        return (($validOrderAmount === true) and ($offerActiveAndNotExpired === true));
    }

    public function checkOfferApplicableOnPayment(Payment\Entity $payment)
    {
        $this->payment = $payment;

        $validPaymentMethod = $this->checkPaymentMethod();

        $validPaymentAmount = $this->checkPaymentAmount();

        $offerActiveAndNotExpired = $this->checkOfferActiveAndNotExpired();

        return (($validPaymentMethod === true) and
                ($validPaymentAmount === true) and
                ($offerActiveAndNotExpired === true));
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
        // Offer is valid across all networks for a payment method
        if ($offerPaymentNetwork === null)
        {
            return true;
        }

        return ($offerPaymentNetwork === $this->payment->getWallet());
    }

    protected function checkNetbanking()
    {
        $offerPaymentNetwork = $this->offer->getPaymentNetwork();

        // Return true if payment network is null for offer
        // Offer is valid across all networks for a payment method
        if ($offerPaymentNetwork === null)
        {
            return true;
        }

        return ($offerPaymentNetwork === $this->payment->getBank());
    }

    /**
     * For emi we are currently just validating against the card
     * TBD if any other validations are required
     * @return [type] [description]
     */
    protected function checkEmi()
    {
        return $this->checkCard();
    }

    protected function checkUpi()
    {
        return true;
    }

    protected function checkCard()
    {
        $card = $this->payment->card;

        $iins = $this->offer->getIins();

        if (empty($iins) === false)
        {
            return (in_array($card->getIin(), $iins, true) === true);
        }

        $validCardType = $this->checkCardType($card);

        $validCardNetwork = $this->checkCardNetwork($card);

        $validCardIsuer = $this->checkCardIssuer($card);

        return (($validCardType === true) and
                ($validCardNetwork === true) and
                ($validCardIsser === true));
    }

    protected function checkCardType(Card\Entity $card)
    {
        $offerPaymentMethodType = $this->offer->getPaymentMethodType();

        // Return true if no payment method type specified on offer
        // Means offer is valid on both credit/debit cards
        if ($offerPaymentMethodType === null)
        {
            return true;
        }

        return ($offerPaymentMethodType === $card->getType());
    }

    protected function checkCardNetwork(Card\Entity $card)
    {
        $offerPaymentNetwork = $this->offer->getPaymentNetwork();

        // Return true if payment network is null for offer
        // Offer is valid across all networks for a payment method
        if ($offerPaymentNetwork === null)
        {
            return true;
        }

        return ($offerPaymentNetwork === $card->getNetworkCode());
    }

    protected function checkCardIssuer(Card\Entity $card)
    {
        $offerCardIssuer = $this->offer->getIssuer();

        if ($offerCardIssuer === null)
        {
            return true;
        }

        return ($offerCardIssuer === $card->getIssuer());
    }

    protected function checkPaymentAmount()
    {
        return ($this->payment->getAmount() >= $this->offer->getMinAmount());
    }

    protected function checkOrderAmount()
    {
         return ($this->order->getAmount() >= $this->offer->getMinAmount());
    }

    protected function checkOfferActiveAndNotExpired()
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $offerExpired = ($now <= $this->offer->getStartsAt()) and ($now > $this->offer->getEndsAt());

        return (($this->offer->isActive() === true) and ($offerExpired === false));
    }
}
