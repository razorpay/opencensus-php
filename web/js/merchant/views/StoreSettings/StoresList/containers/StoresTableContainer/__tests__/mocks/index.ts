import { DEFAULT_STORE_GROUPS } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';

export const STORES_TABLE_STORE_MOCK = {
  storesFilterPayload: {
    limit: 25,
    offset: 10,
    searchTerm: '',
  },
  setStoresFilterOffset: jest.fn(),
  setStoresFilterSearchTerm: jest.fn,
};

export const STORE_GROUPS_STORE_MOCK = {
  modalStatus: null,
  updateModalStatus: jest.fn(),
  selectedStoreGroupInfo: DEFAULT_STORE_GROUPS[0],
  updateDefaultAllStoresGroup: jest.fn(),
};
