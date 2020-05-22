<?php

namespace RZP\Models\D2cBureauDetail;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Constants\IndianStates;

class Core extends Base\Core
{
    public function getOrCreate(Merchant\Detail\Entity $merchantDetails, Merchant\Entity $merchant, User\Entity $user, array $data = []): Entity
    {
        $ownerDetails = $this->repo->d2c_bureau_detail->findByUserIdAndMerchant($user->getId(), $merchant->getId());

        if ($ownerDetails !== null)
        {
            return $ownerDetails;
        }

        $userNameArr = explode(' ', $merchantDetails[Detail\Entity::PROMOTER_PAN_NAME], 2);

        $firstName = $userNameArr[0];

        $lastName = $userNameArr[1] ?? null;

        $state = null;

        if (empty($merchantDetails[Detail\Entity::BUSINESS_REGISTERED_STATE]) === false)
        {
            if ((strlen($merchantDetails[Detail\Entity::BUSINESS_REGISTERED_STATE]) === 2) and
                (IndianStates::stateValueExist($merchantDetails[Detail\Entity::BUSINESS_REGISTERED_STATE])))
            {
                $state = strtoupper($merchantDetails[Detail\Entity::BUSINESS_REGISTERED_STATE]);
            }
            else
            {
                $state = IndianStates::getStateCode($merchantDetails[Detail\Entity::BUSINESS_REGISTERED_STATE]);
            }
        }

        $input = [
            Entity::FIRST_NAME      => $firstName,
            Entity::LAST_NAME       => $lastName,
            Entity::CONTACT_MOBILE  => $user->getContactMobile() ?? null,
            Entity::EMAIL           => $user->getEmail(),
            Entity::ADDRESS         => $merchantDetails[Detail\Entity::BUSINESS_REGISTERED_ADDRESS],
            Entity::CITY            => $merchantDetails[Detail\Entity::BUSINESS_REGISTERED_CITY],
            Entity::STATE           => $state ?? null,
            Entity::PINCODE         => $merchantDetails[Detail\Entity::BUSINESS_REGISTERED_PIN],
            Entity::PAN             => $merchantDetails[Detail\Entity::PROMOTER_PAN],
            Entity::STATUS          => Status::CREATED,
        ];

        if (empty($data) === false)
        {
            $state = null;

            if ((strlen($data[Entity::STATE]) === 2) and
                (IndianStates::stateValueExist($data[Entity::STATE])))
            {
                $state = strtoupper($data[Entity::STATE]);
            }
            else
            {
                $state = IndianStates::getStateCode($data[Entity::STATE]);
            }

            $data[Entity::STATE] = $state;

            $input = $data + [
                    Entity::STATUS => Status::CREATED
                ];
        }

        /** @var Entity $ownerDetails */
        $ownerDetails = (new Entity)->build($input);

        $ownerDetails->merchant()->associate($merchant);

        $ownerDetails->user()->associate($user);

        $this->repo->saveOrFail($ownerDetails);

        $this->trace->info(TraceCode::D2C_BUREAU_DETAILS_CREATE, [
            'd2c_bureau_detail_id'  => $ownerDetails->getId(),
            'merchant_id'           => $merchant->getId(),
            'user_id'               => $user->getId(),
        ]);

        return $ownerDetails;
    }

    public function updateStatusVerified(Entity $bureauDetail)
    {
        $bureauDetail->setStatus(Status::VERIFIED);

        $bureauDetail->setVerifiedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $this->repo->saveOrFail($bureauDetail);
    }
}
