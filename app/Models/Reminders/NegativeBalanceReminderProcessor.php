<?php

namespace RZP\Models\Reminders;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Reminders\Status;
use RZP\Models\Merchant\Reminders\Entity;
use RZP\Models\Merchant\Balance\NegativeReserveBalanceMailers;

class NegativeBalanceReminderProcessor extends ReminderProcessor
{
    const NAMESPACE = 'negative_balance';

    const MAX_REMINDER_COUNT = 7;

    function process(string $entity, string $namespace, string $id, array $input) : array
    {
        $this->trace->info(TraceCode::NEGATIVE_BALANCE_REMINDER_CALLBACK,
            [
                'merchant_id'   => $id,
                'input'          => $input,
            ]
        );

        $this->validateInput($input);

        $reminderCount = isset($input['reminder_count']) === true ? $input['reminder_count'] : 1;

        $merchant = $this->repo->$entity->findOrFail($id);

        $reminderEntity = $this->repo->merchant_reminders->getByMerchantIdAndNamespace($id, self::NAMESPACE);

        if ($reminderCount <= $reminderEntity->getReminderCount())
        {
            $this->trace->info(TraceCode::NEGATIVE_BALANCE_REMINDER_CALLBACK,
                [
                    'msg'            => 'Reminder already sent for this reminder count',
                    'reminder_count' => $reminderCount,
                ]
            );

            return ['success' => true];
        }

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        $balanceAmount = $balance->getBalance();

        if (($this->validateEmail($merchant->getEmail()) === false) or
            ($this->invalidReminder($balanceAmount, $reminderCount, $reminderEntity) === true))
        {
            $this->handleInvalidReminder();
        }

        (new NegativeReserveBalanceMailers)->sendNegativeBalanceBreachReminders($merchant, $reminderCount, $balanceAmount);

        $reminderEntity->setReminderCount($reminderCount);

        if ($reminderCount >= self::MAX_REMINDER_COUNT)
        {
            $reminderEntity->setReminderStatus(Status::COMPLETED);
        }

        $this->repo->merchant_reminders->saveOrFail($reminderEntity);

        return ['success' => true];
    }

    protected function getNamespace() : string
    {
        return self::NAMESPACE;
    }

    protected function validateInput(array $input)
    {
        if(empty($input['reminder_count']))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }
    }

    protected function validateEmail(string $merchantEmail)
    {
        if(empty($merchantEmail) === true)
        {
            return false;
        }

        return true;
    }

    private function invalidReminder(int $balanceAmount, int $reminderCount, Entity $reminderEntity = null)
    {
        if (empty($reminderEntity) === true)
        {
            return true;
        }
        else
        {
            $status = $reminderEntity->getReminderStatus();

            if (($status === Status::DISABLED) or
                ($status === Status::COMPLETED) or
                ($reminderCount > self::MAX_REMINDER_COUNT))
            {
                return true;
            }
            else if ($balanceAmount > 0)
            {
                try
                {
                    $reminderEntity->setReminderStatus(Status::DISABLED);

                    $this->repo->merchant_reminders->saveOrFail($reminderEntity);
                }
                catch(\Exception $e)
                {
                    $this->trace->traceException($e,
                        Trace::ERROR,
                        TraceCode::MERCHANT_REMINDER_SAVE_FAILURE, null);
                }

                return true;
            }
        }

        return false;
    }
}
