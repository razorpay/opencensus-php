import { deepClone } from 'common/utils/rzp-utils';

import type { TerminalsTablePayload } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/stores/terminalsTablePayloadStore';

/**
 * transforms the terminals filter payload to the format for API consumption
 * @param {Array} filterPayload - the filter payload from the store
 * @returns {Object} - the transformed filter payload
 */
export const transformFilterPayload = (filterPayload: TerminalsTablePayload) => {
  const updatedPayload = deepClone(filterPayload);

  // transform the status filter
  const statusFilter = updatedPayload.isActive;
  if (statusFilter === 'ALL') {
    updatedPayload.isActive = null;
  } else {
    updatedPayload.isActive = statusFilter === 'ON';
  }
  // trim the search term
  updatedPayload.searchTerm = updatedPayload.searchTerm?.trim();
  return updatedPayload;
};
