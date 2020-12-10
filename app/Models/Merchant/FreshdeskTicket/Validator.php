<?php
namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TICKET_ID       => 'required|string',
        Entity::TYPE            => 'required|string',
        Entity::TICKET_DETAILS  => 'sometimes',
        Entity::MERCHANT_ID     => 'required|string|alpha_num'
    ];

    protected static $fetchCustomerTicketsRules = [
        Entity::CUSTOMER_EMAIL  => 'required|email',
        'otp'                   => 'required',
        'count'                 => 'sometimes'
    ];

    protected static $getSupportDashboardConversationsRules = [
        Constants::PAGE         => 'required|integer|min:1',
        Constants::PER_PAGE     => 'required|integer|max:100',
        Constants::FD_INSTANCE  => 'required|in:rzp,rzpsol',
    ];

    protected static $createSupportDashboardTicketRules = [
        'name'                                                   => 'required|string',
        'email'                                                  => 'required|email',
        'subject'                                                => 'required|string',
        'description'                                            => 'required|string',
        'phone'                                                  => 'sometimes',
        'attachments'                                            => 'sometimes',
        'priority'                                               => 'required:min:1|max:4',
        'cc_emails'                                              => 'sometimes|array',
        'custom_fields'                                          => 'required|array',
        'custom_fields.cf_requestor_subcategory'                 => 'required',
        'custom_fields.cf_merchant_id_dashboard'                 => 'required',
    ];

    protected static $createSupportDashboardTicketReplyRules = [
        'user_id'       => 'required',
        'body'          => 'sometimes|string',
        'attachments'   => 'sometimes',
    ];

    protected static $getSupportDashboardTicketsRules = [
        Constants::PAGE         => 'required|integer|min:1',
        'per_page'              => 'sometimes|integer|max:100',
        'status'                => 'sometimes|integer|min:2|max:5|nullable',
    ];

    protected static $createSupportDashboardGrievanceRules = [
        'description'           => 'required|string',
        'attachments'           => 'sometimes',
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid type name: ' . $type);
        }
    }

    public function validateCustomerFreshDeskTicketIdFromMerchantNotes($id)
    {
        $idRegex = '/^.*[0-9]+.*$/';

        $validId = (preg_match($idRegex, $id) === 1);

        if ($validId === false)
        {
            throw new Exception\BadRequestValidationFailureException('The id format is invalid.', 'id');
        }
    }
}
