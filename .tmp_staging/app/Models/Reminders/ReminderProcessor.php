<?php

namespace RZP\Models\Reminders;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Services\Reminders;

abstract class ReminderProcessor
{
    protected $app;

    protected $repo;

    protected $trace;

    protected $auth;

    /**
     * @var Reminders
     */

    protected $reminders;

    protected $mode;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->reminders = $this->app['reminders'];

        $this->auth = $this->app['basicauth'];
    }

    abstract function process(string $entity, string $namespace, string $id, array $data);

    public function handleInvalidReminder()
    {
        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE, null,
            [
                'error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE
            ]);
    }
}
