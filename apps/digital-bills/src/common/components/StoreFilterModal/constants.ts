export const ALL_OPTION = { label: 'All', value: 'all' };

export const STORES_SEARCH_BY_FIELDS = {
  STORE_NAME: {
    label: 'Store Name',
    value: 'STORE_NAME',
  },
  STORE_CODE: {
    label: 'Store Code',
    value: 'STORE_CODE',
  },
} as const;

export const DEFAULT_STORE_GROUP = {
  id: 'allStores',
  name: 'All Stores',
  description: 'Default all stores group',
  isActive: true,
  storesCount: 0,
  stores: [],
};
