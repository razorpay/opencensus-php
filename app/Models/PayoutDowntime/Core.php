<?php

namespace RZP\Models\PayoutDowntime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Jobs\ProcessPayoutNotification;
use RZP\Models\Base\PublicCollection as PublicCollection;
use RZP\Exception;

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
            'downtime' => $downtime
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
            'downtime' => $downtime
        ];
    }

    public function fetch($id): Entity
    {
        $downtimeId = Entity::verifyIdAndStripSign($id);

        $this->trace->info(TraceCode::PAYOUT_DOWNTIME_FETCH_BY_ID, (array) $id);

        return $this->repo->payout_downtimes->findOrFailPublic($downtimeId);
    }

    public function fetchAllEnabledDowntimes(): PublicCollection
    {
        $enabled = $this->repo->payout_downtimes->getAllActiveDowntimesByStatus(Constants::ENABLED);

        $this->trace->info(TraceCode::PAYOUT_ENABLED_DOWNTIME, (array) $enabled);

        return $enabled;
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

        if (isset($input[Constants::MID_LIST]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Please provide merchant ids');
        }

        $uniqueMIDs = $this->validateMidsInput($input[Constants::MID_LIST]);

        $status = $downtime->getStatus();

        $emailMessage = $this->updateEmailDetails($status, $downtime);

        if (isset($input[Constants::EMAIL_MESSAGE]) === true and
            empty($input[Constants::EMAIL_MESSAGE]) === false)
        {
            $emailMessage = $input[Constants::EMAIL_MESSAGE];
        }

        $emailData = [
            Constants::MID_LIST      => $uniqueMIDs,
            Constants::STATUS        => $status,
            Constants::EMAIL_MESSAGE => $emailMessage,
            Constants::SUBJECT       => $input[Constants::SUBJECT] ?? Constants::DEFAULT_EMAIL_SUBJECT,
        ];

        $this->trace->info(TraceCode::PROCESS_PAYOUT_NOTIFICATION_JOB_DISPATCHED, $emailData);

        ProcessPayoutNotification::dispatch($this->mode, $emailData, 'pdown_'.$downtime->getId());

        return [
            'desc' => 'Email step is initiated and will be sent shortly'
        ];
    }

    public function validateMidsInput(array $mids): array
    {
        $unique     = array_unique($mids);
        $duplicates = $this->findDuplicates($mids);

        if (empty($duplicates) === false)
        {
            $this->trace->info(TraceCode::PAYOUT_DOWNTIME_DUPLICATE_MID, $duplicates);
        }

        $merchantIds = $this->repo->payout_downtimes->fetchMerchantIdsIfExists($unique)->getIds();

        if (count($unique) !== count($merchantIds))
        {
            $nonBanking = array_diff($unique, $merchantIds);

            throw new Exception\BadRequestValidationFailureException('Invalid merchant ids provided: ' . implode(',', $nonBanking));
        }

        //fetching only banking merchantIds
        $collection = $this->repo->payout_downtimes->fetchBankingMerchantIds($unique)->getStringAttributesByKey('merchant_id');

        $bankingMerchantIds = array_keys($collection);

        // if it's integer like string keys, then array_keys will convert
        // those to integers. Let's re-map to string
        $bankingMerchantIds = array_map('strval', $bankingMerchantIds);

        if (count($unique) !== count($bankingMerchantIds))
        {
            $nonBanking = array_diff($unique, $bankingMerchantIds);

            throw new Exception\BadRequestValidationFailureException('Non banking merchant ids provided: ' . implode(',', $nonBanking));
        }

        return $unique;
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

    private function findDuplicates(array $mids): array
    {
        $duplicates = array();
        foreach (array_count_values($mids) as $val => $c)
        {
            if ($c > 1)
            {
                $duplicates[] = (string) $val;
            }
        }

        return $duplicates;
    }

}
