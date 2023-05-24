<?php

namespace RZP\Models\Emi;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Account;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Support\Collection;
use RZP\Models\Base\PublicCollection;

class Service extends Base\Service
{
    public function all()
    {
        $plans = $this->fetchEmiPlans();

        $plans = $this->formatPlan($plans);

        if ((new Merchant\Service)->isSbiEmiEnabled() === false)
        {
            // SBI EMI is not enabled for the merchant. SBI emi plans will not be returned
            unset($plans[IFSC::SBIN]);
        }

        return $plans;
    }

    public function getEmiPlansAndOptions($offers = null, $order = null)
    {
        $emiPlans = $this->fetchEmiPlans();

        $emiPlansFormatted = $this->formatPlan($emiPlans);

        $emiOptions = [];

        $emiOfferPlans = $this->getSubventedEmiPlansForOffers($offers);

        foreach ($emiPlans as $plan)
        {
            $issuer = $plan->getIssuer();

            // This is a hack to differentiate the debit card emi plan
            // vs the credit card emi plan. For example, for a merchant, there can be
            // both debit and credit hdfc emi plan. Changing the key this way, should
            // not break the current method implementation.
            if ($plan->getType() === Type::DEBIT)
            {
                $issuer .= '_DC';
            }

            $duration = $plan->getDuration();

            // min amount in paisa
            $minAmount = $plan->getMinAmount();

            $merchant_payback = number_format($plan->getMerchantPayback()/100, 2);

            $emiOptionPresent = false;

            if (array_key_exists($plan->getId(), $emiOfferPlans) === true)
            {
                $minEmiAmount = Calculator::calculateMinAmount($minAmount, $plan->getMerchantPayback());

                $minOfferAmount = 0;

                if(isset($emiOfferPlans[$plan->getId()]))
                {
                    $offerId = $emiOfferPlans[$plan->getId()];

                    foreach($offers as $offer)
                    {
                        if ($offer->getPublicId() === $offerId)
                        {
                            $minOfferAmount = $offer->getMinAmount();
                            break;
                        }
                    }
                }

                if ($order->getAmount() >= max($minEmiAmount,$minOfferAmount) )
                {
                    $emiOptions[$issuer][] = [
                        'duration'           => $duration,
                        'interest'           => 0,
                        'subvention'         => Subvention::MERCHANT,
                        'min_amount'         => max($minEmiAmount,$minOfferAmount),
                        'offer_id'           => $emiOfferPlans[$plan->getId()],
                        'merchant_payback'   => $merchant_payback,
                    ];
                }
                else
                {
                    $emiOptions[$issuer][] = [
                        'duration'           => $duration,
                        'interest'           => $plan->getRate() / 100,
                        'subvention'         => Subvention::CUSTOMER,
                        'min_amount'         => $minAmount,
                        'merchant_payback'   => $merchant_payback,
                    ];
                }

                $emiOptionPresent = true;
            }
            // If offer is forced, there's no need to show the other EMI plans
            else if ($this->shouldShowNotOfferEmiOption($offers, $order, $plan) === true)
            {
                $emiOptions[$issuer][] = [
                    'duration'            => $duration,
                    'interest'            => $plan->getRate() / 100,
                    'subvention'          => Subvention::CUSTOMER,
                    'min_amount'          => $minAmount,
                    'merchant_payback'    => $merchant_payback,
                ];

                $emiOptionPresent = true;
            }

            if ($emiOptionPresent)
            {
                $processingFeePlan = (new ProcessingFeePlan())->getProcessingFeePlan($plan->getIssuer(), $plan->getType(), $duration);

                if ($processingFeePlan !== [])
                {
                    $lastEntryOfIssuer = (count($emiOptions[$issuer]) - 1);

                    // update the entry that has been added in this iteration only

                    $emiOptions[$issuer][$lastEntryOfIssuer]['processing_fee_plan'] = $processingFeePlan;
                }

            }

        }

        if ((new Merchant\Service)->isSbiEmiEnabled() === false)
        {
            // SBI EMI is not enabled for the merchant. SBI emi plans will not be returned
            unset($emiOptions[IFSC::SBIN]);

            unset($emiPlansFormatted[IFSC::SBIN]);
        }

        return [
            'plans'     => $emiPlansFormatted,
            'options'   => $emiOptions
        ];
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
        $emiPlan = $this->repo->emi_plan->handleFindOrFail($id);

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

        (new Migration)->handleMigration(Migration::DELETE, $emiPlan);

        $this->repo->emi_plan->deleteOrFail($emiPlan);

        return $emiPlan->toArrayAdmin();
    }

    //Fetches all plans from api and adds them to cps one by one.
    public function migratetoCardPS()
    {
        $allPlans = $this->repo->emi_plan->fetchAllLivePlans();

        $ids = [];
        $failureIds = [];

        foreach($allPlans as $plan)
        {
            $response = (new Migration)->migrate(Migration::CREATE, $plan);

            if($response != null && isset($response[Entity::ID]))
            {
                array_push($ids, $response[Entity::ID]);
            }
            else
            {
                array_push($failureIds, $plan[Entity::ID]);
            }
        }

        return [
            'api_count'         =>  count($allPlans),
            'cps_success_count' =>  count($ids),
            'success_ids'       =>  $ids,
            'failed_ids'        =>  $failureIds,
        ];
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

        $emiPlans = $this->fetchEmiPlans();
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

        if ($offer->getPaymentMethodType() !== $emiPlan->getType())
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

    private function formatPlan($emiPlans)
    {
        $plans = [];

        foreach ($emiPlans as $plan)
        {
            $issuer = $plan->getIssuer();

            if ($plan->getType() === Type::DEBIT)
            {
                $issuer .= '_DC';
            }

            $duration = $plan->getDuration();

            $amount = $plan->getMinAmount();

            // all plans of a bank will have same min amount
            $plans[$issuer][Entity::MIN_AMOUNT] = $amount;

            $plans[$issuer]['plans'][$duration] = $plan->getRate() / 100;
        }

        return $plans;
    }

    private function fetchEmiPlans()
    {
        $sharedCreditEmiPlans = new PublicCollection();

        $sharedDebitEmiPlans = new PublicCollection();

        $merchantCreditEmiPlans = new PublicCollection();

        $merchantDebitEmiPlans = new PublicCollection();

        $id = $this->merchant->getId();

        $methods = $this->merchant->getMethods();

        if (empty($methods) === true)
        {
            return [];
        }

        // Fetch emi plans from CPS/repo based for shared merchant
        $sharedPlans = $this->repo->emi_plan->fetchEmiPlanByMerchantId(Account::SHARED_ACCOUNT);

        // Fetch emi plans from CPS/repo based for current merchant
        $merchantEmiPlans = $this->repo->emi_plan->fetchEmiPlanByMerchantId($id);

        // Filter emi plans for shared/current merchant based on the emi plan type in each entities in the collection
        if ($methods->isCreditEmiEnabled() === true)
        {
            $emiType = Type::CREDIT;

            $enabledProviders = $methods->getEnabledCreditEmiProviders();

            $sharedCreditEmiPlans = $sharedPlans->reject(function($plan) use ($emiType, $enabledProviders) {

            if ($plan->type !== $emiType)
            {
                return true;
            }

            $provider =  $plan->getIssuer();

            return (isset($enabledProviders[$provider]) == false or ($enabledProviders[$provider] === 0));

            });

            $merchantCreditEmiPlans = $merchantEmiPlans->reject(function($plan) use ($emiType, $enabledProviders) {

            if ($plan->type !== $emiType)
            {
                return true;
            }

            $provider =  $plan->getIssuer();

            return (isset($enabledProviders[$provider]) == false  or ($enabledProviders[$provider] === 0));

            });
        }

        if ($methods->isDebitEmiEnabled() === true)
        {
            $emiType = Type::DEBIT;

            // remove providers which are not enabled
            $enabledProviders = $methods->getEnabledDebitEmiProviders();

            $sharedDebitEmiPlans = $sharedPlans->reject(function($plan) use ($emiType, $enabledProviders) {
                if ($plan->type !== $emiType)
                {
                    return true;
                }

                $provider = $plan->bank;

                return ($enabledProviders[$provider] === 0);
            });


            $merchantDebitEmiPlans = $merchantEmiPlans->reject(function($plan) use ($emiType, $enabledProviders) {
                if ($plan->type !== $emiType)
                {
                    return true;
                }

                $provider = $plan->bank;

                return ($enabledProviders[$provider] === 0);
            });

        }

        $issuers = [];

        if ($merchantCreditEmiPlans->isEmpty() !== true)
        {
            foreach ($merchantCreditEmiPlans as $plan)
            {
                $issuer = $plan->getIssuer();
                $type   = $plan->getType();

                $issuers[$issuer][$type] = 1;
            }
        }

        if ($merchantDebitEmiPlans->isEmpty() !== true)
        {
            foreach ($merchantDebitEmiPlans as $plan)
            {
                $issuer = $plan->getIssuer();
                $type   = $plan->getType();

                $issuers[$issuer][$type] = 1;
            }
        }

        // If EMI plan for the issuer and type exists for merchant directly, ignore the shared merchant's emi plan
        if ($sharedCreditEmiPlans->isEmpty() !== true)
        {
            $sharedCreditEmiPlans = $sharedCreditEmiPlans->reject(function ($sharedPlan) use ($issuers)
            {
                $issuer = $sharedPlan->getIssuer();
                $type   = $sharedPlan->getType();

                return (isset($issuers[$issuer][$type]) === true);
            });
        }

        // If EMI plan for the issuer and type exists for merchant directly, ignore the shared merchant's emi plan
        if ($sharedDebitEmiPlans->isEmpty() !== true)
        {
            $sharedDebitEmiPlans = $sharedDebitEmiPlans->reject(function ($sharedPlan) use ($issuers)
            {
                $issuer = $sharedPlan->getIssuer();
                $type   = $sharedPlan->getType();

                return (isset($issuers[$issuer][$type]) === true);
            });
        }

        $merchantEmiPlans = $merchantCreditEmiPlans->merge($merchantDebitEmiPlans);

        $sharedEmiPlans = $sharedCreditEmiPlans->merge($sharedDebitEmiPlans);

        return $merchantEmiPlans->merge($sharedEmiPlans);
    }
}
