<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestValidationFailureException;

class Service extends Base\Service
{
    // Flow: https://razorpay.slack.com/files/U016C1DA1GA/F01BHL2EWAG/automated_customer_managemement_2x__7_.png
    public function handleFreshdeskTicket(array $input)
    {
        $this->trace->info(
            TraceCode::FRESHDESK_DISPUTE_REQUEST,
            ['input' => $input]
        );

        try
        {
            $parsedInput = self::parseInput($input);

            $freshdeskTicket = (new Entity)->build($parsedInput);

            if ($this->shouldProcessFreshdeskTicket($freshdeskTicket) === false)
            {
                return ['success' => true];
            }

            return (new Processor($freshdeskTicket))->process();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FRESHDESK_DISPUTE_REQUEST_ERROR
            );

            return ['success' => false];
        }
    }

    private function shouldProcessFreshdeskTicket(Entity $freshdeskTicket): bool
    {
        $this->trace->info(TraceCode::FRESHDESK_DISPUTE_AUTOMATION_RAZORX_VARIANT, [
            'freshdesk_ticket_id' => $freshdeskTicket->getTicketId(),
            'payment_id'          => $freshdeskTicket->getPaymentId(),
            'razorx_variant'      => 'on',
        ]);

        return true;
    }

    private static function parseInput(array $input)
    {
        if ((isset($input['freshdesk_webhook']) === false) || is_array($input['freshdesk_webhook']) === false)
        {
            throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY);
        }

        $webhookPayload = $input['freshdesk_webhook'];

        return [
            'ticket_id'      => $webhookPayload['ticket_id'] ?? null,
            'subcategory'    => $webhookPayload['ticket_cf_requestor_subcategory'] ?? null,
            'payment_id'     => $webhookPayload['ticket_cf_razorpay_payment_id'] ?? null,
            'reason_code'    => $webhookPayload['ticket_cf_requester_item'] ?? null,
            'customer_name'  => $webhookPayload['ticket_contact_name'] ?? null,
            'customer_email' => $webhookPayload['ticket_contact_email'] ?? null,
        ];
    }
}
