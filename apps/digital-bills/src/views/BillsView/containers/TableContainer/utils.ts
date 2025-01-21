import { deepClone } from '@apps/digital-bills/src/utils/sharedUtils';

/**
 * Function to transform the filter payload
 * @param {Object} payload - filter payload
 * @returns {Object} updatedPayload - updated filter payload
 */
export const transformFilterPayload = (payload) => {
  const updatedPayload = deepClone(payload);
  // Remove the status filter if both the status options are selected or no status is selected
  updatedPayload.status =
    !updatedPayload.status.length || updatedPayload.status.length === 2
      ? undefined
      : updatedPayload.status[0];
  return updatedPayload;
};
