export const DEFAULT_STORE_GROUPS = [
  {
    id: 'allStores',
    name: 'All Stores',
    description: 'Default all stores group',
    isActive: true,
    storesCount: 0,
  },
  {
    id: 'deletedStores',
    name: 'Deleted Stores',
    description: 'A collection of all deleted stores',
    isActive: true,
    storesCount: 0,
  },
];

export const STORE_GROUPS_LIST_LIMIT = 5;

export enum StoreGroupModalStatus {
  CREATE = 'create',
  UPDATE = 'update',
  DELETE = 'delete',
}
