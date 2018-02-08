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

    public function getEmiOptions()
    {
        $emiPlans = $this->repo->emi_plan->fetchEmiPlans();

        $merchantSubventedPlans = $this->repo->merchant_emi_plans->fetchByMerchant($this->merchant->getId());

        $plans = [];

        foreach ($emiPlans as $plan)
        {
            $issuer = $plan->getIssuer();

            $duration = $plan->getDuration();

            // min amount in paisa
            $minAmount = $plan->getMinAmount();

            if (in_array($plan->getId(), $merchantSubventedPlans) === true)
            {
                $minAmount = Calculator::calculateMinAmount($minAmount, $plan->getMerchantPayback());

                $plans[$issuer][] = [
                    'duration'   => $duration,
                    'interest'   => 0,
                    'subvention' => Subvention::MERCHANT,
                    'min_amount' => $minAmount,
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

        return $plans;
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
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST);

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
