<?php

namespace RZP\Models\Merchant\Tnc;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;


class Core extends Base\Core
{
    const MERCHANT_TNC_CREATE_MUTEX_PREFIX = 'api_merchant_tnc_create_';

    public function createOrEditTnc(Detail\Entity $merchantDetails, $input)
    {
        $merchant = $merchantDetails->merchant;

        return $this->repo->transactionOnLiveAndTest(function () use ($merchant, $merchantDetails, $input) {

            $tnc = $merchantDetails->tnc;

            if ($tnc === null)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_TNC_DOES_NOT_EXIST,
                    [
                        'merchant_id' => $merchantDetails->getMerchantId(),
                    ]
                );

                $input[Entity::MERCHANT_ID] = $merchant->getId();

                $tnc = $this->createTnc($merchantDetails, $input);

                (new Merchant\Core)->appendTag($merchant, 'tnc_generated');

                $merchantDetails->setRelation(Detail\Entity::MERCHANT_TNC, $tnc);
            }

            else
            {
                $tnc->edit($input, 'edit');

                $this->repo->merchant_tnc->saveOrFail($tnc);
            }

            $workflowActions = (new Action\Core)->fetchApprovedActionOnEntityOperation(
                $merchant->getId(), 'merchant_detail', Permission\Name::EDIT_ACTIVATE_MERCHANT);

            if ($workflowActions->isNotEmpty() === true)
            {
                $input = [
                    Detail\Entity::ACTIVATION_STATUS => Detail\Status::ACTIVATED,
                ];

                (new Detail\Core)->updateActivationStatus($merchant, $input, $merchant);
            }

            return $this->getTncDetails($tnc);
        });
    }

    private function createTnc($merchantDetails, $input)
    {
        $mutexResource = self::MERCHANT_TNC_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function () use ($merchantDetails, $input) {

            $tnc = new Entity;

            $tnc->generateId();

            $this->trace->info(TraceCode::MERCHANT_TNC_CREATE_DETAILS, $input);

            $tnc->build($input);

            $this->repo->merchant_tnc->saveOrFail($tnc);

            return $tnc;
        });
    }


    public function getTncDetails($tnc)
    {
        if ($tnc !== null)
        {
            $merchant = $tnc->merchantDetail->merchant;

            $tnc = $tnc->toArrayPublic();

            $tnc['link'] = $this->getMerchantTncLink($merchant, $tnc[Entity::ID]);

            unset($tnc[Entity::ID]);
        }

        return $tnc;
    }

    public function getMerchantTncLink(Merchant\Entity $merchant, string $tncId)
    {
        $org = $merchant->getOrgId() ?: $this->app['basicauth']->getOrgId();

        $host = null;

        if($org === ORG_ENTITY::AXIS_ORG_ID)
        {
            $host = env('MERCHANT_TNC_SUBDOMAIN_AXIS');
        }

        if(empty($host) === true)
        {
            $host = env('MERCHANT_TNC_SUBDOMAIN');
        }

        return $host . '/' . $tncId;
    }
}
