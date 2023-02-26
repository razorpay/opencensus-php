<?php

namespace RZP\Models\Workflow\Observer;

use RZP\Models\Workflow\Base;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class Validator extends Base\Validator
{

    protected static $updateObserverDataRules = [
        FDConstants::TICKET_ID        => 'sometimes|integer',
        FDConstants::FD_INSTANCE      => 'sometimes|string',
    ];

    protected static $rejectionReasonDataRules = [
        FDConstants::TICKET_ID                                             => 'sometimes|integer',
        FDConstants::FD_INSTANCE                                           => 'sometimes|string',
        Constants::REJECTION_REASON                                        => 'sometimes|array|size:2',
        Constants::REJECTION_REASON . '.' . Constants::MESSAGE_SUBJECT     => 'sometimes|string',
        Constants::REJECTION_REASON . '.' . Constants::MESSAGE_BODY        => 'sometimes|string',
        Constants::SHOW_REJECTION_REASON_ON_DASHBOARD                      => 'sometimes|string',
    ];

    protected static $approvedTransactionLimitRules = [
        FDConstants::TICKET_ID                                             => 'sometimes|integer',
        FDConstants::FD_INSTANCE                                           => 'sometimes|string',
        Constants::APPROVED_TRANSACTION_LIMIT                              => 'sometimes|integer',
        Constants::REJECTION_REASON                                        => 'sometimes|array|size:2',
        Constants::REJECTION_REASON . '.' . Constants::MESSAGE_SUBJECT     => 'sometimes|string',
        Constants::REJECTION_REASON . '.' . Constants::MESSAGE_BODY        => 'sometimes|string',
    ];

    protected $routeValidatorMapping = [
        Constants::MERCHANT_SAVE_BUSINESS_WEBSITE           => 'rejection_reason_data',
        Constants::MERCHANT_ACTIVATION_UPDATE_WEBSITE       => 'rejection_reason_data',
        Constants::MERCHANT_GSTIN_SELF_SERVE_UPDATE         => 'rejection_reason_data',
        Constants::MERCHANT_BANK_ACCOUNT_CREATE             => 'rejection_reason_data',
        Constants::MERCHANT_BANK_ACCOUNT_UPDATE             => 'rejection_reason_data',
        Constants::ADD_ADDITIONAL_WEBSITE_SELF_SERVE        => 'rejection_reason_data',
        Constants::MERCHANT_ACTIVATION_SAVE                 => 'rejection_reason_data',
        Constants::PARTNER_ACTIVATION_STATUS                => 'rejection_reason_data',
        Constants::INCREASE_TRANSACTION_LIMIT_SELF_SERVE    => 'approved_transaction_limit',
        Constants::MERCHANT_INTERNATIONAL_ENABLEMENT_SUBMIT => 'rejection_reason_data',
    ];

    public function validateWorkflowObserverData($differEntity, array $input)
    {
        $validatorRules = 'update_observer_data';

        if (key_exists($differEntity[DifferEntity::ROUTE], $this->routeValidatorMapping) === true)
        {
            $validatorRules = $this->routeValidatorMapping[$differEntity[DifferEntity::ROUTE]];
        }

        $this->validateInput($validatorRules , $input);
    }

}
