<?php

namespace RZP\Models\RzpKms;


class Constants
{

    const ORG_ID = 'org_id';
    const Org_KEY = 'org';
    const msg = 'Message';
    const ID = 'id';

    const ORG_WORKFLOW_ID = 'org_workflow_id';
    const SERVICE_WORKFLOW_ID = 'service_workflow_id';
    const WORKFLOW_ACTION_ID = 'workflow_action_id';

    const CREATE_L2_TERMINAL_WORKFLOW_ROUTE_NAME = 'create_rzp_kms_terminal_l2_workflow';
    const L1_WORKFLOW_EXECUTE_CONTROLLER = 'RZP\Http\Controllers\RzpKmsController@executeL1Workflow';
    const L2_WORKFLOW_EXECUTE_CONTROLLER_TERMINAL = 'RZP\Http\Controllers\RzpKmsController@executeL2WorkflowTerminal';

    const CreateL1Workflow = 'create l1 workflow';
    const L1EntityCreationKmsRequest = ' L1 entity creation kms request';
    const ExecuteL1Workflow = 'execute l1 workflow';
    const UpdateStatusL1KmsEntity = ' update status l1 kms entity';

    const CreateL2Workflow = 'create l2 workflow';
    const AfterL2WorkflowCreation = 'after l2 workflow creation';
    const L2EntityCreationKmsRequest = ' L2 entity creation kms request';
    const ExecuteL2Workflow = 'execute l2 workflow';
    const UpdateStatusL2KmsEntity = ' update status l2 kms entity';
    const KeyRotationInitiation = 'Initiate Key Rotation';

    const SERVICE_NAME = 'service_name';
    const STATUS = 'status';
    const STEP = 'step';
    const INPUT = 'input';
    const TERMINAL = 'terminals';

    const CREATE_L1_ENTITY_KMS_PATH = "/twirp/rzp.rzp_kms.key_rotation_org_details.v1.OrgKeyRotationDetailsService/CreateOrgDetailsEntity";
    const APPROVE_L1_ENTITY_KMS_PATH = "/twirp/rzp.rzp_kms.key_rotation_org_details.v1.OrgKeyRotationDetailsService/ApproveOrgDetailsEntity";

    const CREATE_L2_ENTITY_KMS_PATH = "/twirp/rzp.rzp_kms.key_rotation_service_details.v1.ServiceKeyRotationDetailsService/CreateServiceDetailsEntity";
    const APPROVE_L2_ENTITY_KMS_PATH = "/twirp/rzp.rzp_kms.key_rotation_service_details.v1.ServiceKeyRotationDetailsService/ApproveServiceDetailsEntity";

    const INITIATE_KEY_ROTATION_KMS_PATH = "/twirp/rzp.rzp_kms.key_rotation.v1.KeyRotationService/InitiateKeyRotationForService";
}
