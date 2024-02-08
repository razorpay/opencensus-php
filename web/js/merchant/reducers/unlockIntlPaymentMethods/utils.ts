import { ICEnablementWorkflowInfo } from 'common/typings';
import {
  ICProductStates,
  ProductWorkflowStatesInBackend,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

const isNCState = (workflowInfo: ICEnablementWorkflowInfo): boolean =>
  !!workflowInfo?.needs_clarification &&
  !!workflowInfo?.tags?.includes('awaiting-customer-response');

export const getKycDocumentStatus = ({
  workflowStatus,
  workflowInfo,
}: {
  workflowStatus: ProductWorkflowStatesInBackend;
  workflowInfo: ICEnablementWorkflowInfo;
}): ICProductStates | null => {
  switch (workflowStatus) {
    case ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED:
      return ICProductStates.NOT_ACTIVATED;
    case ProductWorkflowStatesInBackend.IN_REVIEW:
      if (isNCState(workflowInfo)) {
        return ICProductStates.ACTION_REQUIRED;
      }
      return ICProductStates.UNDER_REVIEW;
    case ProductWorkflowStatesInBackend.APPROVED:
      return ICProductStates.ACTIVE;
    case ProductWorkflowStatesInBackend.REJECTED:
      return ICProductStates.REJECTED;
    default:
      return null;
  }
};
