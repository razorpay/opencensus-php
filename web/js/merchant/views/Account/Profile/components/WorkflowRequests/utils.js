import moment from 'moment';
import { WorkflowStatusDisplayDays, WORKFLOW_TYPES } from './constants';

export const isWorkflowInClarification = (workflow, statuses) => {
  if (!workflow) return false;
  const { workflow_status, needs_clarification, request_under_validation } = workflow;
  return statuses.includes(workflow_status) && needs_clarification && !request_under_validation;
};

export const isVisible = (
  isBankAccountUpdateWorkflow,
  merchantId,
  workflowType = WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
) => {
  if (isBankAccountUpdateWorkflow) {
    const workflowStatus = JSON.parse(window?.localStorage.getItem('workflow_status'));
    const workflowStatusKey = `${workflowType}--${merchantId}`;
    if (workflowStatus?.[workflowStatusKey]) {
      const { expireAt, isVisible } = workflowStatus[workflowStatusKey];
      return isVisible && moment().isBefore(expireAt);
    } else if (workflowType === WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI) {
      return true;
    }
    return false;
  }
  return true;
};

export const showWorkflowStatus = (
  merchantId,
  workflowType = WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
) => {
  const workflowStatusKey = `${workflowType}--${merchantId}`;
  const workflowStatus = JSON.parse(window?.localStorage.getItem('workflow_status') || '{}');
  const newWorkflowStatus = {
    ...workflowStatus,
    [workflowStatusKey]: {
      expireAt: moment().add(WorkflowStatusDisplayDays, 'days').format(),
      isVisible: true,
    },
  };
  window?.localStorage.setItem('workflow_status', JSON.stringify(newWorkflowStatus));
};

export const hideWorkflowStatus = (
  merchantId,
  workflowType = WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
) => {
  const workflowStatus = JSON.parse(window?.localStorage.getItem('workflow_status') || '{}');
  const workflowStatusKey = `${workflowType}--${merchantId}`;
  if (workflowStatus?.[workflowStatusKey]) {
    const newWorkflowStatus = {
      ...workflowStatus,
      [workflowStatusKey]: {
        ...workflowStatus[workflowStatusKey],
        isVisible: false,
      },
    };
    window?.localStorage.setItem('workflow_status', JSON.stringify(newWorkflowStatus));
  }
};
