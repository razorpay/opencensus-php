import { WORKFLOW_TYPES, WorkflowStatusDisplayDays } from './constants';
import moment from 'moment';

export const isWorkflowInClarification = (workflow, statuses) => {
  if (!workflow) return false;
  const { workflow_status, needs_clarification, request_under_validation } = workflow;
  return statuses.includes(workflow_status) && needs_clarification && !request_under_validation;
};

export const isVisible = (isBankAccountUpdateWorkflow, merchantId) => {
  if (isBankAccountUpdateWorkflow) {
    const workflowStatus = JSON.parse(localStorage.getItem('workflow_status'));
    const workflowStatusKey = `${WORKFLOW_TYPES.BANK_DETAIL_UPDATE}--${merchantId}`;
    if (workflowStatus?.[workflowStatusKey]) {
      const { expireAt, isVisible } = workflowStatus[workflowStatusKey];
      return isVisible && moment().isBefore(expireAt);
    }
    return false;
  }
  return true;
};

export const showWorkflowStatus = (merchantId) => {
  const workflowStatusKey = `${WORKFLOW_TYPES.BANK_DETAIL_UPDATE}--${merchantId}`;
  const workflowStatus = JSON.parse(localStorage.getItem('workflow_status') || '{}');
  const newWorkflowStatus = {
    ...workflowStatus,
    [workflowStatusKey]: {
      expireAt: moment().add(WorkflowStatusDisplayDays, 'days').format(),
      isVisible: true,
    },
  };
  localStorage.setItem('workflow_status', JSON.stringify(newWorkflowStatus));
};

export const hideWorkflowStatus = (merchantId) => {
  const workflowStatus = JSON.parse(localStorage.getItem('workflow_status') || '{}');
  const workflowStatusKey = `${WORKFLOW_TYPES.BANK_DETAIL_UPDATE}--${merchantId}`;
  if (workflowStatus?.[workflowStatusKey]) {
    const newWorkflowStatus = {
      ...workflowStatus,
      [workflowStatusKey]: {
        ...workflowStatus[workflowStatusKey],
        isVisible: false,
      },
    };
    localStorage.setItem('workflow_status', JSON.stringify(newWorkflowStatus));
  }
};
