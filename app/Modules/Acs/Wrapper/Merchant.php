<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Merchant\Entity as MerchantEntity;

class Merchant extends Base
{

    function __construct()
    {
        parent::__construct();
        // Initialize Required classes
    }

    //TODO: This is just a skeleton for SaveOrFail Wrapper, update the logic  wherever required
    // This is to be called from  saveOrFail repo method of merchant entity as calling it from repo will require change in
    // repo only else it should be called at every place where merchant SaveOrFail is being called
    function SaveOrFail(MerchantEntity $entity)
    {
        //TODO: Call the Save Api Of Account Service
    }

    function FindOrFail(string $id)
    {
        //TODO: Call the Account Fetch Api Of Account Service and return response
    }
}
