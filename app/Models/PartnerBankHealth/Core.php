<?php

namespace RZP\Models\PartnerBankHealth;

use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends \RZP\Models\Base\Core
{
    /**
     * @var Mutex
     */
    protected $mutex;
    protected $validator;

    const MUTEX_TIMEOUT_LIMIT = 120;
    const MUTEX_RETRY_COUNT = 2;

    const BANK_TO_IFSC_MAPPING = [
        'YESBANK' => 'YESB',
        'ICICI'   => 'ICIC',
        'RBL'     => 'RATN',
        'AXIS'    => 'UTIB',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->validator = new Validator();
    }

    /**
     * This function updates the value of the appropriate key in DB by updating the last_up_at timestamp or last_down_at
     * timestamp for an uptime or downtime webhook respectively. Storing the affected_merchants list is not required in
     * this case but we still do it as doing so would help us extending the implementation for incorporating the usecase
     * of holding payouts at API for enhancing the experience of "intelligent payouts".
     *
     * @param $payload
     */
    public function processFailFastStatusUpdate($payload)
    {
        $this->validator->validateInput(Validator::PARTNER_BANK_HEALTH, $payload);

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_UPDATE_VALIDATION_SUCCESSFUL,
                           [
                               'operation' => Validator::PARTNER_BANK_HEALTH,
                               'payload'   => $payload,
                           ]);

        return $this->updateOrCreatePartnerBankHealthEntity($payload);
    }

    public function updateOrCreatePartnerBankHealthEntity(array $payload)
    {
        $eventType = Notifier::buildEventTypeForIntegration($payload);

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_UPDATE_CREATE_REQUEST, [Entity::EVENT_TYPE => $eventType]);

        $entity = $this->mutex->acquireAndRelease(
            $eventType,
            function() use ($payload, $eventType)
            {
                $partnerBankHealthsEntity = $this->repo->partner_bank_health->getPartnerBankHealthStatusesFromEventType($eventType);

                $this->throwErrorIfUpdateIsInvalid($payload, $partnerBankHealthsEntity);

                $newValue = $this->getUpdatedValue($payload, $partnerBankHealthsEntity);

                if (empty($partnerBankHealthsEntity) === true)
                {
                    return $this->create([Entity::EVENT_TYPE => $eventType, Entity::VALUE => $newValue]);
                }

                return $this->update($partnerBankHealthsEntity, $newValue);
            },
            self::MUTEX_TIMEOUT_LIMIT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            self::MUTEX_RETRY_COUNT
        );

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_UPDATE_CREATE_RESPONSE, $entity->toArrayPublic());

        return $entity;
    }

    public function update(Entity $entity, $newValue)
    {
        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_UPDATE_REQUEST,
                           [
                               'entity' => $entity->toArray(),
                               'input'  => $newValue
                           ]);

        $entity->setValue($newValue);

        $this->repo->partner_bank_health->saveOrFail($entity);

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_UPDATE_RESPONSE, ['entity' => $entity->toArray()]);

        return $entity;
    }

    /** Update is invalid when
     *  1. The uptime webhook is received without a corresponding downtime webhook.
     *  2. The 'begin' timestamp in the webhook is
     *     2.1. less than the begin timestamp of previous webhook for the same status, i.e., up or down
     *     2.2. inconsistent with the current status for a particular channel. For example, if ICIC is down,
     *          an uptime webhook cannot have 'begin' timestamp less than the time when the channel was last marked down
     *
     * @param      $payload
     * @param null $partnerBankHealthsEntity
     *
     * @throws BadRequestValidationFailureException
     */
    public function throwErrorIfUpdateIsInvalid($payload, $partnerBankHealthsEntity = null)
    {
        if (empty($partnerBankHealthsEntity) === true)
        {
            if ($payload[Constants::STATUS] === Status::UP)
            {
                throw new BadRequestValidationFailureException("Uptime webhook received without a corresponding downtime webhook");
            }

            return;
        }

        $value           = $partnerBankHealthsEntity->getValue();
        $bankIfsc        = $payload[Constants::INSTRUMENT][Constants::BANK];
        $integrationType = $payload[Constants::INSTRUMENT][Constants::INTEGRATION_TYPE];

        if ($integrationType === AccountType::DIRECT)
        {
            $partnerBankHealth = $value;
        }
        else
        {
            $partnerBankHealth = $value[$bankIfsc] ?? null;
        }


        if (empty($partnerBankHealth) === true)
        {
            if ($payload[Constants::STATUS] === Status::UP)
            {
                throw new BadRequestValidationFailureException("Uptime webhook received without a corresponding downtime webhook");
            }

            return;
        }

        $this->throwErrorIfTimestampInPayloadIsInconsistent($payload, $partnerBankHealth);
    }

    public function throwErrorIfTimestampInPayloadIsInconsistent($payload, $partnerBankHealth)
    {
        $lastDownAt = $partnerBankHealth[Entity::LAST_DOWN_AT] ?? null;
        $lastUpAt   = $partnerBankHealth[Entity::LAST_UP_AT] ?? null;

        if ($payload[Constants::STATUS] === Status::DOWN)
        {
            $newLastDownAt = $payload[Constants::BEGIN];
        }

        if ($payload[Constants::STATUS] === Status::UP)
        {
            $newLastUpAt = $payload[Constants::BEGIN];
        }

        if (((empty($newLastDownAt) === false) and (empty($lastDownAt) === false) and ($newLastDownAt <= $lastDownAt)) or
            ((empty($newLastUpAt) === false) and (empty($lastUpAt) === false) and ($newLastUpAt <= $lastUpAt)))
        {
            throw new BadRequestValidationFailureException("begin timestamp should be greater than that of the previous webhook");
        }

        if (((empty($newLastDownAt) === false) and (empty($lastUpAt) === false) and ($newLastDownAt <= $lastUpAt)) or
            ((empty($newLastUpAt) === false) and (empty($lastDownAt) === false) and ($newLastUpAt <= $lastDownAt)))
        {
            throw new BadRequestValidationFailureException(
                "begin timestamp should be greater than begin timestamp of previous downtime/uptime webhook");
        }
    }

    public function create($input)
    {
        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_STATUS_CREATE_REQUEST, $input);

        $partnerBankHealthsEntity = new Entity();

        $partnerBankHealthsEntity->setEventType($input[Entity::EVENT_TYPE]);

        $partnerBankHealthsEntity->setValue($input[Entity::VALUE]);

        $this->repo->partner_bank_health->saveOrFail($partnerBankHealthsEntity);

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_STATUS_CREATED, $partnerBankHealthsEntity->toArrayPublic());

        return $partnerBankHealthsEntity;
    }

    /**
     *
     *  Build/Update the value attribute of the entity from the payload for a particular key.
     *  key is concatenation of source, integration_type and mode and channel (IF integration_type is direct)
     *
     * @param array  $payload
     * @param Entity $entity
     *
     * @return array
     */
    public function getUpdatedValue(array $payload, Entity $entity = null)
    {
        $time            = $payload[Constants::BEGIN];
        $partnerBankIfsc = $payload[Constants::INSTRUMENT][Constants::BANK];
        $integrationType = $payload[Constants::INSTRUMENT][Constants::INTEGRATION_TYPE];

        $oldValue = ($entity === null) ? [] : $entity->getValue();
        $newValue = $oldValue;

        if ($payload[Constants::STATUS] === Status::UP)
        {
            //this can only happen if entity is not null, as otherwise, the method throwErrorIfUpdateIsInvalid will
            //throw an exception saying that an uptime webhook was received without a corresponding downtime webhook
            switch ($integrationType)
            {
                case AccountType::SHARED:
                    $newValue[$partnerBankIfsc][Entity::LAST_UP_AT] = $time;
                    break;

                case AccountType::DIRECT:
                    $newValue[Entity::LAST_UP_AT] = $time;
                    break;
            }

        }
        else
        {
            switch ($integrationType)
            {
                case AccountType::SHARED:
                    $newValue[$partnerBankIfsc][Entity::LAST_DOWN_AT] = $time;
                    break;

                case AccountType::DIRECT:
                    $newValue[Entity::LAST_DOWN_AT] = $time;
                    break;
            }
        }

        if (empty($entity) === true)
        {
            // this means the update for the combination of source, integration_type and mode is coming for the
            // first time, and so only include_merchants are affected
            $newValue[Constants::AFFECTED_MERCHANTS] = $payload[Constants::INCLUDE_MERCHANTS];
        }
        else
        {
            $newValue[Constants::AFFECTED_MERCHANTS] = $this->getAffectedMerchants($payload, $newValue[Constants::AFFECTED_MERCHANTS]);
        }

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NEW_VALUE_GENERATED,
                           [
                               'old_value' => $oldValue,
                               'new_value' => $newValue
                           ]);

        return $newValue;
    }

    public function getAffectedMerchants($payload, $currentAffectedMerchants)
    {
        $newAffectedMerchants = [];

        switch ($payload[Constants::STATUS])
        {
            case Status::DOWN:

                /*
                 * If all merchants are currently affected, a downtime webhook will only mean redundant updates
                 * and repeated notification resulting in bad merchant experience. Hence we throw an exception in
                 * this case and do not proceed further.
                 */
                if ($currentAffectedMerchants === ['ALL'])
                {
                    throw new BadRequestValidationFailureException("All merchants are already affected");
                }

                /*
                 * If all merchants are to be affected post this event, we update the affected_merchants list to ["ALL"]
                 */
                if ($payload[Constants::INCLUDE_MERCHANTS][0] === 'ALL')
                {
                    $newAffectedMerchants = ["ALL"];
                    break;
                }

                /*
                 * Otherwise, we add the merchant ids from the include_list who are not present in the current affected
                 * merchants list since post this downtime event, these merchants will face issues with their payouts.
                 */
                $newAffectedMerchants = array_unique(array_merge($currentAffectedMerchants,
                                                                 $payload[Constants::INCLUDE_MERCHANTS]));
                break;

            case Status::UP:

                /*
                 * If no merchants are currently affected, DB updates is redundant and it is also not required
                 * for us to send notifications. So we throw an exception and do not proceed futher.
                 */
                if (empty($currentAffectedMerchants) === true)
                {
                    throw new BadRequestValidationFailureException("No merchants are currently affected");
                }

                /*
                 * In this case, if include_merchants is ["ALL"], merchants who are present in the exclude_list are
                 * still facing issues with their payouts and hence we update the currentAffectedMerchants list to that
                 * of the exclude list.
                 */
                if ($payload[Constants::INCLUDE_MERCHANTS] === ['ALL'])
                {
                    $newAffectedMerchants = $payload[Constants::EXCLUDE_MERCHANTS];
                    break;
                }

                /*
                 * Otherwise, we need to remove the merchants in the include_list from the list of currentAffectedMerchants
                 */
                $newAffectedMerchants = array_diff($currentAffectedMerchants, $payload[Constants::INCLUDE_MERCHANTS]);
                break;
        }

        $this->validator->validateAffectedMerchantsList($newAffectedMerchants);

        if ((empty(array_diff($currentAffectedMerchants, $newAffectedMerchants)) === true) and
            (empty(array_diff($newAffectedMerchants, $currentAffectedMerchants)) === true))
        {
            throw new BadRequestValidationFailureException("affected_merchants list is unchanged",
                                                           null,
                                                           [
                                                               'current_affected_merchants' => $currentAffectedMerchants,
                                                               'new_affected_merchants'     => $newAffectedMerchants
                                                           ]);
        }

        return array_values($newAffectedMerchants);
    }

    /** This method will prepare data for sending notification for partner bank health from the payload.
     *  The same will come in handy if we would want to send notifications to merchants manually or separately.
     *  TODO: expose endpoints to send notifications for partner bank health status updates
     * @param $payload
     */
    public function sendNotifications($payload)
    {
        $data = $this->getNotificationData($payload);

        $notifier = new Notifier();

        $notifier->setNotificationData($data);

        $notifier->sendNotifications();
    }

    public function getNotificationData($payload)
    {
        $data                              = $payload;
        $data[Constants::CHANNEL]          = $payload[Constants::INSTRUMENT][Constants::BANK];
        $data[Constants::INTEGRATION_TYPE] = $payload[Constants::INSTRUMENT][Constants::INTEGRATION_TYPE];

        unset($data[Constants::INSTRUMENT]);

        $this->trace->info(TraceCode::PARTNER_BANK_HEALTH_NOTIFICATION_DATA, $data);

        return $data;
    }
}
