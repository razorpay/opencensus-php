<?php

namespace RZP\Jobs\Kafka;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Commission\Invoice;

class PartnershipsOutboxEventHandlerJob extends Job
{

    public function handle()
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $tracePayload = [
            Constants::JOB_ATTEMPTS => $this->attempts(),
            Constants::MODE         => $this->mode,
            Constants::PAYLOAD      => $this->getPayload(),
            Constants::TASK_ID      => $taskId
        ];

        $this->trace->info(TraceCode::PRTS_EVENT_REQUEST, $tracePayload);

        $this->processEvent($this->getPayload());
    }

    private function processEvent(array $input)
    {
        $operation = $input['type'];

        if ($operation != 'insert')
        {
            return;
        }

        $data   = $input['data'];
        $action = optional($data['action']);
        $core   = new Commission\Core;
        $invoiceCore = new Invoice\Core;

        $response = null;

        switch ($action)
        {
            case Commission\Constants::CREATE_AND_CAPTURE:
                $response = $core->createAndCaptureFromPRTS($data);
                break;

            case Commission\Constants::CAPTURE:
                $response = $core->captureFromPRTS($data);
                break;

            case Invoice\Constants::CREATE_ISSUED:
                $response = $invoiceCore->createIssuedFromPRTS($data);
                break;

            case Invoice\Constants::CREATE_AND_FINANCE_WORKFLOW:
                $response = $invoiceCore->createInvoiceAndFinanceWorkflowFromPRTS($data);
                break;

            case Invoice\Constants::FINANCE_WORKFLOW:
                $response = $invoiceCore->createFinanceWorkflowFromPRTS($data);
                break;

            case Invoice\Constants::CREATE_AND_SETTLEMENT:
                $response = $invoiceCore->createInvoiceAndSettlementTDSFromPRTS($data);
                break;

            case Invoice\Constants::SETTLEMENT:
                $response = $invoiceCore->settlementTDSFromPRTS($data);
                break;

            default:
                $this->trace->error(TraceCode::PRTS_EVENT_INVALID_ACTION, [
                    'input' => $input,
                ]);
        }

        if (isset($response) === true)
        {
            // send ack event to partnerships service
            $app = App::getFacadeRoot();
            $app->partnerships->dispatchAckToPRTS($response, $action);
        }
    }
}
