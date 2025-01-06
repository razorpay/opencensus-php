import create from 'zustand';

import type {
  StoreLinkedProductsType,
  StoreType,
  StoreSearchColumnType,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';
import type { PaginationLimitType } from 'merchant/views/BillMeSettings/common/types';

export type StoresTablePayload = {
  limit: PaginationLimitType;
  offset: number;
  searchTerm: string | undefined;
  linkedProducts: StoreLinkedProductsType[] | null;
  storeType: StoreType;
  searchColumn: StoreSearchColumnType;
};

const storesFilterPayload: StoresTablePayload = {
  limit: 10,
  offset: 0,
  searchTerm: '',
  linkedProducts: null,
  storeType: 'ALL',
  searchColumn: 'STORE_NAME',
};

type StoresTablePayloadState = {
  storesFilterPayload: StoresTablePayload;
  setStoresFilterLimit: (limit: PaginationLimitType) => void;
  setStoresFilterOffset: (offset: number) => void;
  setStoresFilterSearchTerm: (searchTerm: string | undefined) => void;
  setStoresFilterStoreType: (storeType: StoreType) => void;
  setStoresFilterLinkedProducts: (linkedProducts: StoreLinkedProductsType[]) => void;
  setStoreSearchByColumn: (searchColumn: StoreSearchColumnType) => void;
  resetPayloadFilters: () => void;
};

export const useStoresTablePayloadStore = create<StoresTablePayloadState>((set) => ({
  storesFilterPayload,
  setStoresFilterOffset: (offset) =>
    set((state) => ({ ...state, storesFilterPayload: { ...state.storesFilterPayload, offset } })),
  setStoresFilterLimit: (limit) =>
    set((state) => ({
      ...state,
      storesFilterPayload: { ...state.storesFilterPayload, offset: 0, limit },
    })),
  setStoresFilterStoreType: (storeType) =>
    set((state) => ({
      ...state,
      storesFilterPayload: { ...state.storesFilterPayload, storeType },
    })),
  setStoresFilterLinkedProducts: (linkedProducts) =>
    set((state) => ({
      ...state,
      storesFilterPayload: {
        ...state.storesFilterPayload,
        linkedProducts,
      },
    })),
  setStoresFilterSearchTerm: (searchTerm) =>
    set((state) => ({
      ...state,
      storesFilterPayload: {
        ...state.storesFilterPayload,
        searchTerm,
      },
    })),
  setStoreSearchByColumn: (searchColumn) =>
    set((state) => ({
      ...state,
      storesFilterPayload: {
        ...state.storesFilterPayload,
        searchColumn,
      },
    })),
  resetPayloadFilters: () =>
    set((state) => ({
      ...state,
      storesFilterPayload,
    })),
}));
