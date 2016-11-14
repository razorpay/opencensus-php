<?php

namespace RZP\Http\Controllers;

use RZP\Gateway\Upi;
use Illuminate\Support\Facades\DB;

class UpiController extends Controller
{   
    public function __construct()
    {
        parent::__construct();
    }

    public function addVpaProvider()
    {
        while (true) 
        {
            $recordsToUpdate = $this->repo->upi->fetchAllForProviderUpdate();
            
            if (count($recordsToUpdate) === 0)
            {
                break;
            }
            
            foreach($recordsToUpdate as $upiRecord) 
            {
                $providerCode = $upiRecord->getProviderFromVpa();

                $upiRecord->setProvider($providerCode);

                $upiRecord->saveOrFail();
            }
            
            if (count($recordsToUpdate) < 500)
            {
                break;
            }
        } 
    }

}