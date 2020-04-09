<?php


namespace RZP\Models\Batch\Processor;

use RZP\Models\BankTransferHistory;

class BankTransferEdit extends Base
{
    public function addSettingsIfRequired(& $input)
    {
        $config =[];

        if (isset($input['config']) === true)
        {
            $config = $input['config'];
        }

        $config[BankTransferHistory\Entity::CREATED_BY] = $this->app['basicauth']->getAdmin()->getEmail();

        $input['config'] = $config;
    }
}
