<?php

namespace RZP\Models\Emi;

use Illuminate\Support\Facades\App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Repository as Repo;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID             => 'required|string|size:14',
        Entity::BANK                    => 'required_without_all:network,cobranding_partner|size:4',
        Entity::NETWORK                 => 'required_without_all:bank,cobranding_partner|max:5|in:AMEX,BAJAJ',
        Entity::COBRANDING_PARTNER      => 'required_without_all:bank,network|in:onecard',
        Entity::TYPE                    => 'sometimes|in:credit,debit',
        Entity::DURATION                => 'required|integer|in:2,3,6,9,12,18,24',
        Entity::RATE                    => 'required|integer|min:0',
        Entity::METHODS                 => 'sometimes|in:card,wallet,netbanking',
        Entity::MIN_AMOUNT              => 'sometimes|integer|min:100',
        Entity::ISSUER_PLAN_ID          => 'sometimes',
        Entity::SUBVENTION              => 'sometimes|in:customer,merchant',
        Entity::MERCHANT_PAYBACK        => 'required|integer',
    );

    protected static $createValidators = array(
        Entity::BANK,
        Entity::DURATION,
    );

    // 2 month duration is only valid for bajaj
    protected function validateDuration($input)
    {
        if (isset($input[Entity::DURATION]) && $input[Entity::DURATION] == 2)
        {
            if (isset($input[Entity::NETWORK]) === true && $input[Entity::NETWORK] === 'BAJAJ')
            {
                return;
            }
            throw new Exception\BadRequestValidationFailureException('The selected duration is invalid.');
        }

    }

    protected function validateBank($input)
    {
        if (isset($input[Entity::BANK]) === false)
        {
            return;
        }

        if (isset($input[Entity::NETWORK]) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Either of bank or network must be sent in input');
        }

        if (in_array($input[Entity::BANK], Gateway::$emiBanks, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('invalid bank name: '. $input[Entity::BANK]);
        }
    }

    public function validateExistingEmiPlan()
    {
        $newEmiPlan = $this->entity;

        $params = [
            Entity::MERCHANT_ID => $newEmiPlan->getMerchantId(),
            Entity::DURATION    => $newEmiPlan->getDuration(),
            Entity::TYPE        => $newEmiPlan->getType(),
        ];

        if (($newEmiPlan->getNetwork() === null) && ($newEmiPlan->getCobrandingPartner() === null))
        {
            $params[Entity::BANK] = $newEmiPlan->getBank();
        }
        else if (($newEmiPlan->getBank() === null) && ($newEmiPlan->getCobrandingPartner() === null))
        {
            $params[Entity::NETWORK] = $newEmiPlan->getNetwork();
        }
        else
        {
            $params[Entity::COBRANDING_PARTNER] = $newEmiPlan->getCobrandingPartner();
        }

        $repo = App::getFacadeRoot()['repo'];

        $existingEmis = $repo->emi_plan->handleFetch($params);

        $count = $existingEmis->count();

        if ($count > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMI_PLAN_EXIST);
        }
    }
}
