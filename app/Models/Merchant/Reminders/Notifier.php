<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Services\Reminders;
use RZP\Models\Merchant\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Reminders\Entity as RemindersEntity;

class Notifier extends Base\Core
{
    const SHARED_MERCHANT_ID = '100000Razorpay';
    /**
     * @var Entity
     */
    protected $merchant;
    /**
     * @var Reminders
     */
    protected $reminders;

    public function __construct(Entity $merchant)
    {
        parent::__construct();

        $this->mode = $this->app['rzp.mode'];

        $this->reminders = $this->app['reminders'];

        $this->merchant = $merchant;
    }

    public function createOrUpdateReminder(string $namespace, int $issuedAt): bool
    {
        $reminderEntity = $this->repo->merchant_reminders
                                      ->getByMerchantIdAndNamespace($this->merchant->getId(), $namespace);

        $reminderEntityExists = (empty($reminderEntity) === false);
        $reminderIdExists = (($reminderEntityExists === true) and
                             (empty($reminderEntity->getReminderId()) === false));
        $reminderStatusDisabled = (($reminderEntityExists === true) and
                                    ($reminderEntity->getReminderStatus() === Status::DISABLED));

        if (($reminderEntityExists === false) or
            ($reminderIdExists === false) or
            ($reminderStatusDisabled === true))
        {
            $request = $this->getRemindersCreateReminderInput($namespace, $issuedAt);

            $response = $this->reminders->createReminder($request, self::SHARED_MERCHANT_ID);

            $this->trace->info(TraceCode::REMINDERS_RESPONSE,
                [
                    'reminders_response' => $response,
                    'action'              => 'create',
                ]
            );

            $this->setReminderResponse($namespace, $response, $reminderEntity);
        }
        else if ($reminderIdExists === true)
        {
            $reminderId = $reminderEntity->getReminderId();

            $request = $this->getRemindersUpdateReminderInput($issuedAt);

            $response = $this->reminders->updateReminder($request, $reminderId, self::SHARED_MERCHANT_ID);

            $this->trace->info(TraceCode::REMINDERS_RESPONSE,
                    [
                        'reminders_response'    => $response,
                        'action'                 => 'update',
                    ]
                );

            if(empty($response['id']) === true)
            {
                return false;
            }

            $reminderEntity->setReminderStatus(Status::IN_PROGRESS);

            $this->repo->saveOrFail($reminderEntity);
        }

        return true;
    }

    protected function setReminderResponse(string $namespace, $response, RemindersEntity $reminderEntity = null)
    {
        if(empty($response['id']) === false)
        {
            if (empty($reminderEntity) === true)
            {
                $input = [
                    RemindersEntity::REMINDER_STATUS    => Status::PENDING,
                    RemindersEntity::REMINDER_NAMESPACE => $namespace,
                    RemindersEntity::REMINDER_COUNT     => 0,
                ];

                $reminderEntity = (new Core)->createOrUpdate($input, $this->merchant, $reminderEntity);
            }

            $reminderEntity->setReminderNamespace($namespace);

            $reminderEntity->setReminderStatus(Status::IN_PROGRESS);

            $reminderEntity->setReminderId($response['id']);

            $this->repo->saveOrFail($reminderEntity);
        }
        else
        {
            if (empty($reminderEntity) === false)
            {
                $reminderEntity->setReminderStatus(Status::FAILED);
            }
        }

    }

    protected function getRemindersCreateReminderInput(string $namespace, int $issuedAt): array
    {
        $reminderData = [
            'issued_at' => $issuedAt,
        ];

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $this->merchant->getId(),
            'entity_type'   => $this->merchant->getEntityName(),
            'reminder_data' => $reminderData,
            'callback_url'  => $this->getCallbackUrlForReminder($namespace),
        ];

        return $request;
    }

    protected function getCallbackUrlForReminder(string $namespace)
    {
        $baseUrl = 'reminders/send';

        $mode = $this->mode;

        $entity = $this->merchant->getEntityName();

        $merchantId = $this->merchant->getId();

        $callbackURL = sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $merchantId);

        return $callbackURL;
    }

    public function deleteReminder(string $namespace) : bool
    {
        $reminderEntity = $this->repo->merchant_reminders
                                     ->getByMerchantIdAndNamespace($this->merchant->getId(), $namespace);

        if ((empty($reminderEntity) === false) and
            ($reminderEntity->getReminderStatus() != Status::DISABLED))
        {
            if ((empty($reminderEntity->getReminderId()) === false))
            {
                try
                {
                    $this->reminders->deleteReminder($reminderEntity->getReminderId(), self::SHARED_MERCHANT_ID);
                }
                catch(\Throwable $e)
                {
                    $this->trace->traceException($e,
                        Trace::ERROR,
                        TraceCode::REMINDER_DELETE_FAILURE);
                }
            }

            $reminderEntity->setReminderStatus(Status::DISABLED);

            $this->repo->merchant_reminders->saveOrFail($reminderEntity);

            return true;
        }

        return false;
    }

    protected function getRemindersUpdateReminderInput(int $issuedAt): array
    {
        $reminderData = [
            'issued_at' => $issuedAt,
        ];

        $request = [
            'reminder_data' => $reminderData
        ];

        return $request;
    }

}
