<?php

namespace RZP\Models\PayoutDowntime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Jobs\ProcessPayoutNotification;
use RZP\Models\Base\PublicCollection as PublicCollection;
use RZP\Models\Merchant\Entity as Merchant;

class Core extends Base\Core
{

    public function create(array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_DOWNTIME_CREATE, $input);

        $downtime = (new Entity)->build($input);

        $this->repo->payout_downtimes->saveOrFailEntity($downtime);

        $response = $this->sendDowntimeMail($downtime, $input);

        return [
            'desc'     => $response['desc'],
            'downtime' => $downtime->toArrayAdmin(),
        ];

    }

    public function edit($id, array $input): array
    {
        $downtimeId = Entity::verifyIdAndStripSign($id);

        $downtime = $this->repo->payout_downtimes->findOrFailPublic($downtimeId);

        (new Validator())->editValidations($downtime, $input);

        $this->trace->info(TraceCode::PAYOUT_DOWNTIME_EDIT, $input);

        $downtime->edit($input);

        $this->repo->payout_downtimes->saveOrFailEntity($downtime);

        $response = $this->sendDowntimeMail($downtime, $input);

        return [
            'desc'     => $response['desc'],
            'downtime' => $downtime->toArrayAdmin(),
        ];
    }

    public function fetch($id): Entity
    {
        $downtimeId = Entity::verifyIdAndStripSign($id);

        $this->trace->info(TraceCode::PAYOUT_DOWNTIME_FETCH_BY_ID, (array) $id);

        return $this->repo->payout_downtimes->findOrFailPublic($downtimeId);
    }

    public function fetchAllEnabledDowntimes(Merchant $merchant): array
    {
        $enabled = $this->repo->payout_downtimes->getAllActiveDowntimesByStatus(Constants::ENABLED);

        $this->trace->info(TraceCode::PAYOUT_ENABLED_DOWNTIME, (array) $enabled);

        return $this->filterEnabledDowntimesForMerchants($enabled, $merchant);
    }

    public function filterEnabledDowntimesForMerchants(PublicCollection $enabledDowntimes, Merchant $merchant): array
    {
        $downtime = array();

        $channels = $enabledDowntimes->getStringAttributesByKey(Entity::CHANNEL);

        if(empty($channels) === true)
        {
            return $downtime;
        }

        $va = $this->repo->payout_downtimes->fetchActiveVirtualAccountForMerchantIds(array($merchant->getId()));

        $ba = $this->repo->payout_downtimes->fetchActiveCurrentAccountForMerchantIds(array($merchant->getId()), strtolower(Constants::RBL), Constants::CURRENT);

        foreach ($enabledDowntimes as $key => $entity)
        {
            if($entity->channel === Constants::POOL_NETWORK and
               empty($va) === false)
            {
                array_push($downtime, $entity->toArrayAdmin());
            }
            else if($entity->channel === Constants::RBL and
               empty($ba) === false)
            {
                array_push($downtime, $entity->toArrayAdmin());
            }
            else if($entity->channel === Constants::ALL)
            {
                array_push($downtime, $entity->toArrayAdmin());
            }
        }

        return $downtime;
    }

    public function fetchAllDowntimes($input): PublicCollection
    {
        $downtime = $this->repo->payout_downtimes->fetch($input);

        $this->trace->info(TraceCode::PAYOUT_DOWNTIME_ALL, (array) $downtime);

        return $downtime;
    }

    public function isEmailOptionSelected(Entity $downtime): bool
    {
        if (($downtime->getStatus() === Constants::ENABLED and
             $downtime->getEnabledEmailOption() === 'Yes') or
            ($downtime->getStatus() === Constants::DISABLED and
             $downtime->getDisabledEmailOption() === 'Yes'))
        {
            return true;
        }

        return false;
    }

    public function sendDowntimeMail(Entity $downtime, array $input): array
    {
        if($this->isEmailOptionSelected($downtime) === false)
        {
            return [
                'desc' => 'Email option is not selected'
            ];
        }

        $status = $downtime->getStatus();

        $emailMessage = $this->updateEmailDetails($status, $downtime);

        if (isset($input[Constants::EMAIL_MESSAGE]) === true and
            empty($input[Constants::EMAIL_MESSAGE]) === false)
        {
            $emailMessage = $input[Constants::EMAIL_MESSAGE];
        }

        $emailData = [
            Constants::STATUS        => $status,
            Constants::EMAIL_MESSAGE => $emailMessage,
            Constants::CHANNEL       => $downtime->getChannel(),
            Constants::SUBJECT       => $input[Constants::SUBJECT] ?? Constants::DEFAULT_EMAIL_SUBJECT,
        ];

        $this->trace->info(TraceCode::PROCESS_PAYOUT_NOTIFICATION_JOB_DISPATCHED, $emailData);

        ProcessPayoutNotification::dispatch($this->mode, $emailData, 'pdown_'.$downtime->getId());

        return [
            'desc' => 'Email step is initiated and will be sent shortly'
        ];
    }

    public function updateEmailDetails(string $status, Entity $downtime): string
    {
        $emailMessage = null;

        if ($status === Constants::ENABLED)
        {
            $downtime->setEnabledEmailStatus(Constants::PROCESSING);
            $emailMessage = $downtime->getDowntimeMessage();
        }
        else
        {
            if ($status === Constants::DISABLED)
            {
                $downtime->setDisabledEmailStatus(Constants::PROCESSING);
                $emailMessage = $downtime->getUpTimeMessage();
            }
        }

        $this->repo->payout_downtimes->saveOrFail($downtime);

        return $emailMessage;
    }

}
