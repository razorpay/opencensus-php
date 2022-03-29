import {
  NC_ADD_WEBSITE,
  NC_UPDATE_WEBSITE,
  NC_ADD_ADDITIONAL_WEBSITE,
  NC_INCREASE_TXN_LIMIT,
  NC_UPDATE_BANK_ACC,
  NC_UPDATE_GSTIN,
} from '../../deeplink-constants';

/**
 * @constant
 * @description constant variables for `WorkflowType` strings for consistency over files
 */
export const WORKFLOW_TYPES = {
  INCREASE_TRANSACTION_LIMIT: 'increase_transaction_limit',
  INCREASE_INTERNATIONAL_TRANSACTION_LIMIT: 'increase_international_transaction_limit',
  BANK_DETAIL_UPDATE: 'bank_detail_update',
  ADD_ADDITIONAL_WEBSITE: 'add_additional_website',
  UPDATE_BUSINESS_WEBSITE: 'additional_website', // this is a little confusing but this is set by the backend
  ADD_BUSINESS_WEBSITE: 'additional_website',
  UPDATE_GSTIN: 'gstin_update_self_serve',
};

/**
 * @constant
 * @description Maps `WorkflowRoute` -> `WorkflowName`
 * @description `WorkflowName` is used as header on Needs Clarification Modal
 */
export const workflowNamesMap = {
  [NC_INCREASE_TXN_LIMIT]: 'Increase Transaction Limit',
  [NC_UPDATE_BANK_ACC]: 'Update Bank Account Details',
  [NC_ADD_ADDITIONAL_WEBSITE]: 'Add Additional Website',
  [NC_UPDATE_WEBSITE]: 'Update Business Website',
  [NC_ADD_WEBSITE]: 'Add Business Website',
  [NC_UPDATE_GSTIN]: 'Update GSTIN Details',
};

/**
 * @description Maps `WorkflowRoute` to `WorkflowType`
 * @description used for triggering specific `WorkflowType` based on `WorkflowRoute` in dashboard URL
 */
export const worklowTypesMap = {
  [NC_INCREASE_TXN_LIMIT]: WORKFLOW_TYPES.INCREASE_TRANSACTION_LIMIT,
  [NC_UPDATE_BANK_ACC]: WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
  [NC_ADD_ADDITIONAL_WEBSITE]: WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE,
  [NC_ADD_WEBSITE]: WORKFLOW_TYPES.ADD_BUSINESS_WEBSITE,
  [NC_UPDATE_WEBSITE]: WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE,
  [NC_UPDATE_GSTIN]: WORKFLOW_TYPES.UPDATE_GSTIN,
};

export const getWorkflowNameForRoute = (route) => {
  return workflowNamesMap[route];
};

export const getWorkflowTypeForRoute = (route) => {
  return worklowTypesMap[route];
};
