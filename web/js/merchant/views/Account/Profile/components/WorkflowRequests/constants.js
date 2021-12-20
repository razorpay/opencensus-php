import {
  NC_ADD_WEBSITE,
  NC_UPDATE_WEBSITE,
  NC_INCREASE_TXN_LIMIT,
  NC_UPDATE_BANK_ACC,
  NC_UPDATE_GSTIN,
} from '../../deeplink-constants';

/**
 * @constant
 * @description constant variables for `WorkflowType` strings for consistency over files
 */
export const WORKFLOWS = {
  INCREASE_TRANSACTION_LIMIT: 'increase_transaction_limit',
  BANK_DETAIL_UPDATE: 'bank_detail_update',
  ADD_ADDITIONAL_WEBSITE: 'add_additional_website',
  UPDATE_BUSINESS_WEBSITE: 'update_business_website',
  UPDATE_GSTIN: 'gstin_update_self_serve',
};

/**
 * @constant
 * @description Maps `WorkflowType` -> `WorkflowName`
 * @description `WorkflowName` is used as header on Needs Clarification Modal
 */
export const workflowNames = {
  [WORKFLOWS.INCREASE_TRANSACTION_LIMIT]: 'Increase Transaction Limit',
  [WORKFLOWS.BANK_DETAIL_UPDATE]: 'Update Bank Details',
  [WORKFLOWS.ADD_ADDITIONAL_WEBSITE]: 'Add Additional Website',
  [WORKFLOWS.UPDATE_BUSINESS_WEBSITE]: 'Update Business Website',
  [WORKFLOWS.UPDATE_GSTIN]: 'Update GSTIN Details',
};

/**
 * @description Maps `WorkflowRoute` to `WorkflowType`
 * @description used for triggering specific `WorkflowType` based on `WorkflowRoute` in dashboard URL
 * @param {string} route - Worklow Route from deeplink constants
 * @returns {string} `WorkflowType`
 */
export const getWorkflowTypeForRoute = (route) => {
  switch (route) {
    case NC_INCREASE_TXN_LIMIT:
      return WORKFLOWS.INCREASE_TRANSACTION_LIMIT;
    case NC_UPDATE_BANK_ACC:
      return WORKFLOWS.BANK_DETAIL_UPDATE;
    case NC_ADD_WEBSITE:
      return WORKFLOWS.ADD_ADDITIONAL_WEBSITE;
    case NC_UPDATE_WEBSITE:
      return WORKFLOWS.UPDATE_BUSINESS_WEBSITE;
    case NC_UPDATE_GSTIN:
      return WORKFLOWS.UPDATE_GSTIN;
    default:
      return null;
  }
};
