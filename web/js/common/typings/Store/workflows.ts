import { WorkflowStates } from 'common/constant/enums';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';

export type CommonWorkflowInfo<T extends WORKFLOW_TYPES> = {
  loading: boolean;
  error: null | string;
  permission: T;
};

export type ICEnablementWorkflowInfo =
  CommonWorkflowInfo<WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI> & {
    needs_clarification?: string | null;
    request_under_validation?: boolean;
    tags?: string[];
    workflow_exists?: boolean;
    workflow_status?: WorkflowStates;
    workflow_created_at?: number;
    rejection_reason_message?: string;
    workflow_rejected_at?: number;
  };

export type WorkflowsReducerState = {
  // TODO: Add appropriate types for other workflows
  increase_transaction_limit: CommonWorkflowInfo<WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT>;
  increase_international_transaction_limit: CommonWorkflowInfo<WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT>;
  add_additional_website: CommonWorkflowInfo<WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT>;
  additional_website: CommonWorkflowInfo<WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT>;
  bank_detail_update: CommonWorkflowInfo<WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT>;
  gstin_update_self_serve: CommonWorkflowInfo<WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT>;
  toggle_international_revamped: ICEnablementWorkflowInfo;
  international_products_pa_cb_enablement: ICEnablementWorkflowInfo;
};
