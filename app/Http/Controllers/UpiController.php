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
        $batchSize = 500;
        
        while (true) 
        {
            $recordsToUpdate = $this->repo->upi->fetchAllForProviderUpdate($batchSize);
            
            if (count($recordsToUpdate) === 0)
            {
                break;
            }
            
            foreach($recordsToUpdate as $upiRecord) 
            {
                $providerCode = $upiRecord->extractProviderFromVpa();

                $upiRecord->setProvider($providerCode);

                $upiRecord->saveOrFail();
            }
            
            if (count($recordsToUpdate) < $batchSize)
            {
                break;
            }
        } 
    }

}