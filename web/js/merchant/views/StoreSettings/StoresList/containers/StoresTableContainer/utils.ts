import { deepClone } from 'common/utils/rzp-utils';
import { StoresTablePayload } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';

import type { StoreGroupForListing } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';

/**
 * transforms the stores list filter payload to the format for API consumption
 * @param {Array} filterPayload - the filter payload from the store
 * @param {Object} selectedStoreGroupInfo - the selected store group info
 * @returns {Object} - the transformed filter payload
 */

export const transformFilterPayload = (
  filterPayload: StoresTablePayload,
  selectedStoreGroupInfo: StoreGroupForListing,
) => {
  const updatedPayload = deepClone(filterPayload);
  // transform the 'storeType' filter
  if (updatedPayload.storeType === 'ALL') {
    updatedPayload.storeType = null;
  }

  // set the 'storeGroupId'
  updatedPayload.storeGroupId = selectedStoreGroupInfo?.id;
  if (
    selectedStoreGroupInfo?.id === 'allStores' ||
    selectedStoreGroupInfo?.id === 'deletedStores'
  ) {
    updatedPayload.storeGroupId = null;
  }

  // set the 'isDeleted' flag
  updatedPayload.isDeleted = selectedStoreGroupInfo?.id === 'deletedStores';

  // trim the search term
  updatedPayload.searchTerm = updatedPayload.searchTerm?.trim();

  return updatedPayload;
};
