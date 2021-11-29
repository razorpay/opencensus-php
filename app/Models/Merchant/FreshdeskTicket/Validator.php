<?php
namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TICKET_ID       => 'required|string',
        Entity::TYPE            => 'required|string',
        Entity::TICKET_DETAILS  => 'sometimes',
        Entity::MERCHANT_ID     => 'required|string|alpha_num'
    ];

    protected static $createCustomerTicketRules = [
        'email'                                  => 'required|email',
        'otp'                                    => 'required|string|min:4|max:6',
        'name'                                   => 'required|string|max:100',
        'phone'                                  => 'sometimes|contact_syntax',
        'description'                            => 'required|string|max:1000',
        'subject'                                => 'required|string|max:500',
        'attachments'                            => 'sometimes',
        'attachments.*'                          => 'custom:attachment',
        'custom_fields'                          => 'required|array',
        'custom_fields.cf_requester_category'    => 'required|string|max:50',
        'custom_fields.cf_requestor_subcategory' => 'required|string|max:100',
        'custom_fields.cf_transaction_id'        => 'required_if:custom_fields.cf_requester_category,Customer|string|min:8|max:50',
        'custom_fields.cf_razorpay_payment_id'   => 'required_if:custom_fields.cf_requester_category,Customer|string|min:8|max:50',
    ];

    protected static $raiseGrievanceRules = [
        'id'                                  => 'required',
        'email'                               => 'required|email',
        'description'                         => 'required|string|max:1000',
        'attachments'                         => 'sometimes',
        'attachments.*'                       => 'custom:attachment',
        'custom_fields'                       => 'sometimes|array',
    ];

    protected static $fetchCustomerTicketsRules = [
        Entity::CUSTOMER_EMAIL  => 'required|email',
        'otp'                   => 'required',
        'count'                 => 'sometimes'
    ];

    protected static $getSupportDashboardConversationsRules = [
        Constants::PAGE         => 'required|integer|min:1',
        Constants::PER_PAGE     => 'required|integer|max:100',
    ];

    protected static $getSupportDashboardXConversationsRules = [
        Constants::PAGE         => 'required|integer|min:1',
        Constants::PER_PAGE     => 'required|integer|max:100',
    ];

    protected static $createSupportDashboardTicketRules = [
        'name'                                                   => 'sometimes|string',
        'email'                                                  => 'required|email',
        'group_id'                                               => 'sometimes',
        'subject'                                                => 'required|string',
        'description'                                            => 'required|string',
        'responder_id'                                           => 'sometimes|int|custom:responder_id',
        'type'                                                   => 'sometimes|string|custom:ticket_type',
        'phone'                                                  => 'sometimes',
        'attachments'                                            => 'sometimes',
        'attachments.*'                                          => 'custom:attachment',
        'priority'                                               => 'required:min:1|max:4',
        'cc_emails'                                              => 'sometimes|array',
        'custom_fields'                                          => 'required|array',
        'custom_fields.cf_requestor_subcategory'                 => 'sometimes',
        'custom_fields.cf_merchant_id_dashboard'                 => 'required',
        'fd_instance'                                            => 'sometimes',
        'custom_fields.cf_category'                              => 'sometimes|string|custom:custom_field_category',
        'custom_fields.cf_subcategory'                           => 'sometimes|string|custom:custom_field_sub_category',
        'custom_fields.cf_product'                               => 'sometimes|string|custom:custom_field_product',
        'custom_fields.cf_ticket_queue'                          => 'sometimes|string|custom:custom_field_ticket_queue',
        'custom_fields.cf_created_by'                            => 'sometimes|string',
        'tags'                                                   => 'sometimes|array',
        'status'                                                 => 'sometimes',
    ];

    protected static $createSupportDashboardTicketMobileSignupRules = [
        'name'                                                   => 'required|string',
        'group_id'                                               => 'sometimes',
        'email'                                                  => 'sometimes|email',
        'subject'                                                => 'required|string',
        'description'                                            => 'required|string',
        'responder_id'                                           => 'sometimes|int|custom:responder_id',
        'type'                                                   => 'sometimes|string|custom:ticket_type',
        'phone'                                                  => 'required',
        'attachments'                                            => 'sometimes',
        'attachments.*'                                          => 'custom:attachment',
        'priority'                                               => 'required:min:1|max:4',
        'cc_emails'                                              => 'sometimes|array',
        'custom_fields'                                          => 'required|array',
        'custom_fields.cf_requestor_subcategory'                 => 'sometimes',
        'custom_fields.cf_merchant_id_dashboard'                 => 'required',
        'fd_instance'                                            => 'sometimes',
        'custom_fields.cf_category'                              => 'sometimes|string|custom:custom_field_category',
        'custom_fields.cf_subcategory'                           => 'sometimes|string|custom:custom_field_sub_category',
        'custom_fields.cf_product'                               => 'sometimes|string|custom:custom_field_product',
        'custom_fields.cf_ticket_queue'                          => 'sometimes|string|custom:custom_field_ticket_queue',
        'custom_fields.cf_created_by'                            => 'sometimes|string',
        'tags'                                                   => 'sometimes|array',
        'status'                                                 => 'sometimes',
    ];

    protected static $createSupportDashboardXTicketRules = [
        'name'                                                   => 'sometimes|string',
        'email'                                                  => 'required|email',
        'subject'                                                => 'required|string',
        'description'                                            => 'required|string',
        'phone'                                                  => 'sometimes',
        'attachments'                                            => 'sometimes',
        'priority'                                               => 'required:min:1|max:4',
        'cc_emails'                                              => 'sometimes|array',
        'custom_fields'                                          => 'required|array',
        'custom_fields.cf_requestor_subcategory'                 => 'sometimes',
        'custom_fields.cf_merchant_id_dashboard'                 => 'required',
        'fd_instance'                                            => 'sometimes',
        'status'                                                 => 'sometimes',
    ];

    protected static $createSupportDashboardTicketReplyRules = [
        'user_id'       => 'required',
        'body'          => 'sometimes|string',
        'attachments'   => 'sometimes',
        'attachments.*' => 'custom:attachment',
    ];

    protected static $createSupportDashboardXTicketReplyRules = [
        'user_id'       => 'required',
        'body'          => 'sometimes|string',
        'attachments'   => 'sometimes',
    ];

    protected static $getSupportDashboardTicketsRules = [
        Constants::PAGE                     => 'required|integer|min:1',
        'per_page'                          => 'sometimes|integer|max:100',
        'status'                            => 'sometimes|integer|min:2|max:5|nullable',
        Constants::CF_REQUESTOR_CATEGORY    => 'sometimes',
        Constants::CF_REQUESTOR_SUBCATEGORY => 'sometimes',
        Constants::CF_REQUESTOR_ITEM        => 'sometimes',
        Constants::CF_CREATED_BY            => 'sometimes',
    ];

    protected static $getSupportDashboardXTicketsRules = [
        Constants::PAGE         => 'required|integer|min:1',
        'per_page'              => 'sometimes|integer|max:100',
        'status'                => 'sometimes|integer|min:2|max:5|nullable',
    ];

    protected static $createSupportDashboardGrievanceRules = [
        'description'           => 'required|string',
        'attachments'           => 'sometimes',
        'attachments.*'         => 'custom:attachment',

    ];

    /*
     * From: https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
     */
    const VALID_EXTENSION_MIMETYPE_MAP = [
        // audio
        'aif'       => ['audio/x-aiff',],
        'cda'       => ['application/x-cdf'],
        'mp3'       => ['audio/mpeg',],
        'mpa'       => ['audio/mpeg'],
        'ogg'       => ['audio/ogg'],
        'oga'       => ['audio/ogg'],
        'wav'       => ['audio/wav'],
        'weba'	    => ['audio/webm'],
        'wma'       => ['audio/x-ms-wma'],
        // data
        'csv'       => ['text/csv', 'text/plain'],
        'dat'       => ['application/dat'],
        'log'       => ['text/plain'],
        'xml'       => ['text/xml'],
        // images
        'bmp'       => ['image/bmp'],
        'gif'       => ['image/gif'],
        'ico'       => ['image/x-icon', 'image/vnd.microsoft.icon'],
        'jpg'       => ['image/jpeg'],
        'jpeg'      => ['image/jpeg'],
        'png'       => ['image/png'],
        'svg'       => ['image/svg+xml'],
        'tif'       => ['image/tiff'],
        'tiff'      => ['image/tiff'],
        // media
        '3g2'       => ['video/3gpp2', 'audio/3gpp2'],
        '3gp'       => ['video/3gpp', 'audio/3gpp'],
        'avi'       => ['video/x-msvideo'],
        'flv'       => ['video/x-flv'],
        'h264'      => ['audio/mp4m, video/mp4'],
        'm4v'       => ['video/m4v'],
        'mkv'       => ['video/x-matroska'],
        'mov'       => ['video/quicktime'],
        'mp4'       => ['video/mp4'],
        'mpg'       => ['video/mpeg'],
        'mpeg'      => ['video/mpeg'],
        'rm'        => ['application/vnd.rn-realmedia'],
        'wmv'       => ['video/x-ms-wmv'],
        // documents
        'doc'       => ['application/msword'],
        'docx'      => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'odt'       => ['application/vnd.oasis.opendocument.text'],
        'pdf'       => ['application/pdf'],
        'rtf'       => ['application/rtf'],
        'txt'       => ['text/plain'],
        'xls'       => ['application/vnd.ms-excel'],
        'xlsx'      => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ods'       => ['application/vnd.oasis.opendocument.spreadsheet'],
    ];

    protected static $createSupportDashboardXGrievanceRules = [
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

    protected function validatePriorityString($attribute, $priority)
    {
        if (Priority::isValidPriorityString($priority) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid priority string: ' . $priority,
                Constants::PRIORITY
            );
        }
    }

    protected function validateFdInstance($attribute, string $fdInstance)
    {
        if (Instance::isValidFdInstance($fdInstance) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid fd instance: ' . $fdInstance,
                Constants::FD_INSTANCE
            );
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

    protected function validateAttachment($attribute, $attachment)
    {
        if ($this->shouldValidateAttachment() === false)
        {
            return;
        }

        $extension = strtolower($attachment->getClientOriginalExtension());

        $mimeType = strtolower($attachment->getMimeType());

        $data = [
            'extension' => $extension,
            'mime_type' => $mimeType,
        ];

        if (isset(self::VALID_EXTENSION_MIMETYPE_MAP[$extension]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid Extension', $attribute, $data);
        }

        $validMimeTypesForExtension = self::VALID_EXTENSION_MIMETYPE_MAP[$extension];

        if (in_array($mimeType, $validMimeTypesForExtension) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid Extension', $attribute, $data);
        }
    }

    protected function shouldValidateAttachment(): bool
    {
        $app = \App::getFacadeRoot();

        $taskId = $app['request']->getTaskId();

        $variant = $app['razorx']->getTreatment($taskId, Constants::RAZORX_FLAG_VALIDATE_FRESHDESK_ATTACHMENT_EXTENSION,
            $app['rzp.mode'] ?? Mode::LIVE);

        return $variant !== 'control';
    }

    protected function validateResponderId($attribute, $value)
    {
        $validResponderIds = $this->getValidResponderIds();

        if (in_array($value, $validResponderIds, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Responder Id: ' . $value);
        }
    }

    protected function validateTicketType($attribute, $value)
    {
        $validTicketTypes = $this->getValidTicketTypes();

        if (in_array($value, $validTicketTypes, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Ticket Type: ' . $value);
        }
    }

    protected function getValidTicketTypes()
    {
        return [
            Constants::SERVICE_REQUEST_TICKET_TYPE,
        ];
    }

    protected function validateCustomFieldTicketQueue($attribute, $value)
    {
        $validQueues = $this->getValidCustomFieldTicketQueues();

        if (in_array($value, $validQueues, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Ticket Queue: ' . $value);
        }
    }

    protected function getValidCustomFieldTicketQueues()
    {
        return [
            Constants::MERCHANT_TICKET_QUEUE,
        ];
    }

    protected function validateCustomFieldProduct($attribute, $value)
    {
        $validProducts = $this->getValidCustomFieldProduct();

        if (in_array($value, $validProducts, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Custom Field Product: ' . $value);
        }
    }

    protected function getValidCustomFieldProduct()
    {
        return [
            Constants::PAYMENT_GATEWAY_CF_PRODUCT,
        ];
    }

    protected function validateCustomFieldCategory($attribute, $value)
    {
        $validCategories = $this->getValidCustomFieldCategory();

        if (in_array($value, $validCategories, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Category Id: ' . $value);
        }
    }

    protected function getValidCustomFieldCategory()
    {
        return [
            Constants::ACTIVATION_CF_CATEGORY,
        ];
    }

    protected function validateCustomFieldSubCategory($attribute, $value)
    {
        $validSubCategories = $this->getValidCustomFieldSubCategory();

        if (in_array($value, $validSubCategories, true) === false)
        {
            throw new BadRequestValidationFailureException('Invalid Subcategory Id: ' . $value);
        }
    }

    protected function getValidCustomFieldSubCategory()
    {
        return [
            Constants::ACTIVATION_CF_SUBCATEGORY,
        ];
    }

    protected function getValidResponderIds()
    {
        $app = \App::getFacadeRoot();

        return [
            (int)$app['config']->get('applications.freshdesk.activation.rzp.agentId'),
        ];
    }
}
