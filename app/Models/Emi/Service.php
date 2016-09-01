<?php

namespace RZP\Models\Emi;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Emi;

class Service extends Base\Service
{
    public function all()
    {
        $emiPlans = $this->repo->emi_plan->getAllEmiPlans();

        return $emiPlans->toArrayPublic();
    }

    public function fetch($id)
    {
        $emiPlans = $this->repo->emi_plan->findOrFail($id);

        return $emiPlans->toArrayPublic();
    }

    public function addEmiPlan(array $input)
    {
        $emiPlan = (new Core)->addEmiPlan($input);

        return $emiPlan->toArrayPublic();
    }

    public function deleteEmiPlan($id)
    {
        $emiPlan = $this->repo->emi_plan->findOrFailPublic($id);

        $this->repo->emi_plan->deleteOrFail($emiPlan);

        return $emiPlan->toArrayPublic();
    }

    public function getEmiFiles(array $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        // default list of banks
        $emiFileBanks = Payment\Gateway::$emiBanksUsingCardTerminals;

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
            $bank = Emi\Issuer::$emiFileBanks[$bankIfsc];

            $returnValue[$bankIfsc] = $this->generateEmiFileForBank($bankIfsc, $from, $to, $bank);
        }

        return $returnValue;
    }

    protected function generateEmiFileForBank($bankIfsc, $from, $to, $bank)
    {
        $emiPaymentsForBank = (new Payment\Repository)->fetchEmiPaymentsBetween($from, $to, $bankIfsc);

        $count = $emiPaymentsForBank->count();

        if ($count === 0)
        {
            return ['count' => $count];
        }

        $class = $this->getEmiFileClass($bank);

        return (new $class)->generate($emiPaymentsForBank);
    }

    protected function getEmiFileClass($bank)
    {
        $bankName = explode('_', $bank)[0];

        return 'RZP\Models\Emi\Banks\\'.ucfirst($bankName).'\EmiFile';
    }

    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp - 1;

        if (isset($input['on']))
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], 'Asia/Kolkata');

            $fromTimeStamp = $from->timestamp;

            $to = $from->addDay()->timestamp - 1;

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
