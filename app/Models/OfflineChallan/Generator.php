<?php

namespace RZP\Models\OfflineChallan;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Generator extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    protected function buildOfflineChallanEntity(Base\PublicEntity $entity): Entity
    {
        $offlineChallan = new Entity();

        $offlineChallan->virtualAccount()->associate($entity);

        return $offlineChallan;
    }


    public function generate(Base\PublicEntity $entity): Entity
    {
        $offlineChallanInput = $this->getOfflineChallanInput($entity);

        $offlineChallan = $this->buildOfflineChallanEntity($entity);

        $offlineChallan->build($offlineChallanInput);

        $this->repo->saveOrFail($offlineChallan);

        return $offlineChallan;
    }


    protected function getOfflineChallanInput(
        Base\PublicEntity $entity): array
    {
        $challanNumber = $this->generateChallanNumber();

        $offlineInput[Entity::CHALLAN_NUMBER] = $challanNumber;

        $offlineInput[Entity::STATUS] = Status::PENDING;

        $offlineInput[Entity::VIRTUAL_ACCOUNT_ID] = $entity->getId();

        $params['offline'] = true;

        $params['gateway'] = 'offline_hdfc';

        $params['merchant_id'] = $entity->getMerchantId();

        $this->trace->info(TraceCode::TERMINAL_SELECTION,
            [
                'Params for fetch'  => $params,

            ]);

        $terminalData = $this->repo->terminal->getByParams($params);

        $this->trace->info(TraceCode::TERMINAL_SELECTION,
            [
                'Terminal Count'    => $terminalData ?? 'Not Set',
            ]);

        if($terminalData->count() === 0)
        {
            throw new Exception\RuntimeException(
                'No terminal found.',
                null,
                null,
                ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);
        }

        $offlineInput[Entity::CLIENT_CODE]  = $terminalData[0]->gateway_merchant_id;

        /*
         * Currently bank name is hard coded since we only have offline payment for HDFC bank
        This is kept commented for future use for other banks
         */

      //  $offlineInput[Entity::BANK_NAME] = $entity->merchant->org->getDisplayName();

        $offlineInput[Entity::BANK_NAME] = 'HDFC';

        return $offlineInput;
    }

    protected function generateChallanNumber()
    {
       $challan_number = random_alphanum_string(16);

       $response = $this->repo->offline_challan->fetchByChallanNumber($challan_number);

       while ($response !== null)
       {
           $challan_number = random_alphanum_string(16);

           $response = $this->repo->offline_challan->fetchByChallanNumber($challan_number);
       }

       return $challan_number;
    }


}
