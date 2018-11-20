<?php

namespace RZP\Models\Emi;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;

class Service extends Base\Service
{
    public function all()
    {
        $emiPlans = $this->repo->emi_plan->fetchEmiPlans();

        $plans = [];

        foreach ($emiPlans as $plan)
        {
            $issuer = $plan->getIssuer();

            $duration = $plan->getDuration();

            $amount = $plan->getMinAmount();

            // all plans of a bank will have same min amount
            $plans[$issuer][Entity::MIN_AMOUNT] = $amount;

            $plans[$issuer]['plans'][$duration] = $plan->getRate() / 100;
        }

        return $plans;
    }

    public function getEmiOptions($offers = null, $order = null)
    {
        $emiPlans = $this->repo->emi_plan->fetchEmiPlans();

        $plans = [];

        $emiOfferPlans = $this->getSubventedEmiPlansForOffers($offers);

        foreach ($emiPlans as $plan)
        {
            $issuer = $plan->getIssuer();

            $duration = $plan->getDuration();

            // min amount in paisa
            $minAmount = $plan->getMinAmount();

            if (array_key_exists($plan->getId(), $emiOfferPlans) === true)
            {
                $minEmiAmount = Calculator::calculateMinAmount($minAmount, $plan->getMerchantPayback());

                if ($order->getAmount() >= $minEmiAmount)
                {
                    $plans[$issuer][] = [
                        'duration'   => $duration,
                        'interest'   => 0,
                        'subvention' => Subvention::MERCHANT,
                        'min_amount' => $minEmiAmount,
                        'offer_id'   => $emiOfferPlans[$plan->getId()],
                    ];
                }
                else
                {
                    $plans[$issuer][] = [
                        'duration'   => $duration,
                        'interest'   => $plan->getRate() / 100,
                        'subvention' => Subvention::CUSTOMER,
                        'min_amount' => $minAmount,
                    ];
                }
            }
            // If offer is forced, there's no need to show the other EMI plans
            else if ($this->shouldShowNotOfferEmiOption($offers, $order, $plan) === true)
            {
                $plans[$issuer][] = [
                    'duration'   => $duration,
                    'interest'   => $plan->getRate() / 100,
                    'subvention' => Subvention::CUSTOMER,
                    'min_amount' => $minAmount,
                ];
            }
        }

        return $plans;
    }

    protected function shouldShowNotOfferEmiOption($offers, $order, $plan): bool
    {
        // If there's no order involved, there's no reason to do any filtering
        if ($order === null)
        {
            return true;
        }

        // Whether regular EMI options are to be shown
        // now depends on whether the order is forced-EMI
        if (($offers->count() === 1) and
            ($order->isOfferForced() === true))
        {
            $offer = $offers->first();

            if ($this->checkIfPlanMatchesOffer($plan, $offer) === true)
            {
                return true;
            }

            return false;
        }

        // For not forced offers, again there's no need to filter anything
        return true;
    }

    public function fetch($id)
    {
        $emiPlan = $this->repo->emi_plan->findOrFail($id);

        return $emiPlan->toArrayAdmin();
    }

    public function addEmiPlan(array $input)
    {
        $emiPlan = (new Core)->addEmiPlan($input);

        return $emiPlan->toArrayAdmin();
    }

    public function deleteEmiPlan($id)
    {
        $emiPlan = $this->repo->emi_plan->findOrFailPublic($id);

        $this->repo->emi_plan->deleteOrFail($emiPlan);

        return $emiPlan->toArrayAdmin();
    }

    public function getEmiFiles(array $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        $email = $input['email'] ?? null;

        //
        // Only ICIC and YESB emi files will be sent via this route now, as they
        // are sent via FTP. All other emi files which are sent via mail
        // use gateway_file.
        //
        $emiFileBanks = [
            IFSC::ICIC,
            IFSC::YESB,
        ];

        // if input bank is set, emi file to be processed for only that bank
        if (isset($input['bank']))
        {
            $bankIfsc = $input['bank'];

            IFSC::exists($bankIfsc);

            $emiFileBanks = array($bankIfsc);
        }

        $returnValue = [];

        foreach ($emiFileBanks as $bankIfsc)
        {
            $bank = Issuer::$emiFileBanks[$bankIfsc];

            $returnValue[$bankIfsc] = $this->generateEmiFileForBank($bankIfsc, $from, $to, $bank, $email);
        }

        return $returnValue;
    }

    /**
     * This function will fetch emi plan for given
     * offer if it is merchant subvented.
     *
     * The resulting array will look like
     * [planId1 => offerIdx, planId2 => offerIdy]
     *
     * @param array $offers
     * @return array
     */
    protected function getSubventedEmiPlansForOffers($offers)
    {
        if (empty($offers) === true)
        {
            return [];
        }

        $emiPlans = $this->repo->emi_plan->fetchEmiPlans();
        $emiOfferPlans = [];

        $offers->map(function ($offer) use($emiPlans, & $emiOfferPlans) {
            if ($offer->getEmiSubvention() !== true)
            {
                return;
            }

            if (($offer->isActive() === false) or
                ($offer->isPeriodActive() === false))
            {
                return;
            }

            foreach($emiPlans as $emiPlan)
            {
                if ($this->checkIfPlanMatchesOffer($emiPlan, $offer) === true)
                {
                    $emiOfferPlans = [$emiPlan->getId() => $offer->getPublicId()] + $emiOfferPlans;
                }
            }
        });

        return $emiOfferPlans;
    }

    protected function checkIfPlanMatchesOffer($emiPlan, $offer)
    {
        $bank = $offer->getIssuer();

        if (($bank !== null) and
            ($emiPlan->getBank() !== $bank))
        {
            return false;
        }

        $network = $offer->getPaymentNetwork();

        if (($network !== null) and
            ($emiPlan->getNetwork() !== $network))
        {
            return false;
        }

        $durations = $offer->getEmiDurations() ?: Entity::VALID_DURATIONS;

        if (in_array($emiPlan->getDuration(), $durations, true) === false)
        {
            return false;
        }

        return true;
    }

    protected function generateEmiFileForBank($bankIfsc, $from, $to, $bank, $email = null)
    {
        $emiPaymentsForBank = $this->repo->payment->fetchEmiPaymentsWithCardTerminalsBetween($from, $to, $bankIfsc);

        $count = $emiPaymentsForBank->count();

        if ($count === 0)
        {
            return ['count' => $count];
        }

        $class = $this->getEmiFileClass($bank);

        return (new $class)->generate($emiPaymentsForBank, $email);
    }

    protected function getEmiFileClass($bank)
    {
        $bankName = explode('_', $bank)[0];

        return 'RZP\Models\Emi\Banks\\'.ucfirst($bankName).'\EmiFile';
    }

    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

        if (isset($input['on']))
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST)->startOfDay();

            $fromTimeStamp = $from->getTimestamp();

            $to = $from->addDay()->getTimestamp() - 1;

            $from = $fromTimeStamp;
        }
        else
        {
            if (isset($input['from']))
            {
                $from = $input['from'];
            }

            if (isset($input['to']))
            {
                $to = $input['to'];
            }
        }

        return array($from, $to);
    }

}
