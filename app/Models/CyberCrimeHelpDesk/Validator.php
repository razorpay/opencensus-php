<?php


namespace RZP\Models\CyberCrimeHelpDesk;

use RZP\Base\Validator as BaseValidator;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment;
use RZP\Models\Card;


class Validator extends BaseValidator
{
    protected static $sendMailToLEAFromCyberCrimeHelpdeskRules = [
        'requester_mail'    => 'required|email|max:255',
        'payment_requests'  => 'required|array',
        'files'             => 'sometimes|array'
    ];

    protected static $cyberCrimeHelpdeskWorkflowActionCreateRules = [
        'requester_mail'                            =>  'required|email|max:255',
        'ticket_data'                               =>  'required|array|size:3',
        'ticket_data.ticket'                        =>  'required|array|min:1',
        'ticket_data.file_names'                    =>  'sometimes|array',
        'ticket_data.fd_ticket_id'                  =>  'required|string|max:255',
        'enable_share_beneficiary_details_checkbox' =>  'required|boolean',
    ];

    /**
     * @throws BadRequestException
     */
    public function validateApprovedPaymentWithQueryData($payment, $requestData)
    {
        if($payment->getMethod() !== $requestData[Payment\Entity::METHOD])
        {
            throw new BadRequestException('Payment Details are not matching the query asked for payment '. $payment->getId());
        }

        switch ($payment->getMethod())
        {
            case Constants::UPI:
                if ($payment->getReference16() !== $requestData[Payment\Entity::REFERENCE16])
                {
                    throw new BadRequestException('Payment Details are not matching the query asked for payment '. $payment->getId());
                }
                break;
            case Constants::NETBANKING:
                if ($payment->getReference1() !== $requestData[Payment\Entity::REFERENCE1])
                {
                    throw new BadRequestException('Payment Details are not matching the query asked for payment '. $payment->getId());
                }
            case Constants::CARD:
                if (empty($requestData[Payment\Entity::REFERENCE2]) === false
                    && $payment->getReference2() !== $requestData[Payment\Entity::REFERENCE2])
                {
                    throw new BadRequestException('Payment Details are not matching the query asked for payment '. $payment->getId());
                }
        }
    }

}
