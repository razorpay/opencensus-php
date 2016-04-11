<?php

namespace Models\Emi;

use Carbon\Carbon;

use Models\Base;
use Models\Payment;
use Models\Bank\IFSC;


class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    public function all()
    {
        $emiPlans = $this->repo->getAllEmiPlans();

        return $emiPlans->toArrayPublic();
    }

    public function fetch($id)
    {
        $emiPlans = $this->repo->findOrFail($id);

        return $emiPlans->toArrayPublic();
    }

    public function addEmiPlan(array $input)
    {
        $emiPlan = (new Core)->addEmiPlan($input);

        return $emiPlan->toArrayPublic();
    }

    public function deleteEmiPlan($id)
    {
        $emiPlan = $this->repo->findOrFailPublic($id);

        $this->repo->deleteOrFail($emiPlan);

        return $emiPlan->toArrayPublic();
    }

    public function getEmiFiles(array $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        $emiFileBanks = Payment\Gateway::$emiFileBanks;

        if (isset($input['bank']))
        {
            $bankIfsc = $emiFileBanks[$input['bank']];

            $returnValue[$bankIfsc] = $this->generateEmiFileForBank($bankIfsc, $from, $to, $input['bank']);
        }
        else
        {
            foreach ($emiFileBanks as $bank => $bankIfsc)
            {
                $returnValue[$bankIfsc] = $this->generateEmiFileForBank($bankIfsc, $from, $to, $bank);
            }
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

        return 'Models\Emi\Banks\\'.ucfirst($bankName).'\EmiFile';
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
