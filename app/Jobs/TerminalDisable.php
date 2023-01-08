<?php

namespace RZP\Jobs;

use App;
use DateTime;
use DateTimeZone;
use RZP\Trace\TraceCode;
use RZP\Services\Reminders;
use RZP\Models\Terminal\Service;
use Razorpay\Trace\Logger as Trace;


class TerminalDisable extends Job
{
    const JOB_RELEASE_WAIT = 0;
    const MAX_JOB_ATTEMPTS = 5;
    const REMINDER_NAMESPACE = 'upi_terminals_enable';
    const SHARED_MERCHANT_ID = '100000Razorpay';

    private  $data;

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->sendEnableReminder();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::TERMINAL_ENABLE_REMINDER_FAILURE,
                $this->data);

                $this->handleRefundJobRelease();

                // return from here becasuse reminder could not be set.
                return;
        }

        try
        {
            $input = [
                'toggle' => false,
            ];

            (new Service)->toggleTerminal($this->data['terminal_id'], $input);

            $this->trace->info(
                TraceCode::TERMINAL_QUEUE_DISABLE_SUCCESS,
                $this->data
            );

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->data['job_attempts'] = $this->attempts();

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TERMINAL_QUEUE_DISABLE_FAILURE,
                $this->data);

            $this->handleRefundJobRelease();
        }
    }

    protected function sendEnableReminder()
    {
        $terminalId = $this->data['terminal_id'];

        $paymentId = $this->data['payment_id'];

        $callbackUrl = sprintf('terminals/toggle/%s/%s',$terminalId, 'enable');

        $timeZone = new DateTimeZone('Asia/Kolkata');

        $date = new DateTime('tomorrow', $timeZone);

        $input = [
            'namespace'         => self::REMINDER_NAMESPACE,
            'entity_id'         => $paymentId,
            'entity_type'       => 'terminal',
            'reminder_data'     => [
                'enable_at' => $date->getTimestamp(),
            ],
            'callback_url'      => $callbackUrl,
        ];

        $app = App::getFacadeRoot();

        $response = $app['reminders']->createReminder($input, self::SHARED_MERCHANT_ID) ;

        $this->trace->info(
            TraceCode::TERMINAL_ENABLE_REMINDER_SUCCESS,
            $response
        );
    }

    protected function handleRefundJobRelease()
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::TERMINAL_QUEUE_DISABLE_DELETE,
                [
                    'data'         => $this->data,
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries or if error code is not retriable.'
                ]
            );

            $this->delete();
        }
        else
        {
            //
            // When queue_driver is sync, there's no release
            // and hence it's as good as deleting the job.
            //
            $this->release(self::JOB_RELEASE_WAIT);
        }
    }
}
