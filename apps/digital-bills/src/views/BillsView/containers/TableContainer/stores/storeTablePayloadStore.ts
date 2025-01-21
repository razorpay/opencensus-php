import create from 'zustand';

import { pastDate, currDate } from '@apps/digital-bills/src/utils/helpers/getDateRangeFromInterval';
import { DurationRange } from '@apps/digital-bills/src/utils/constants';

import type { StoresType } from '@apps/digital-bills/src/common/components/StoreFilterModal/types';
import type {
  PageLimitType,
  StoreStatus,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type StoreTablePayload = {
  limit: PageLimitType;
  offset: number;
  status: StoreStatus[];
  fromDate: string | null;
  toDate: string | null;
  minAmount: number | null;
  maxAmount: number | null;
  storeCode: string;
  storeName: string;
  storeIds: string[];
};

const storeFilterPayload: StoreTablePayload = {
  limit: 10,
  offset: 0,
  status: [],
  fromDate: pastDate(DurationRange.Last30Days),
  toDate: currDate(),
  minAmount: null,
  maxAmount: null,
  storeCode: '',
  storeName: '',
  storeIds: [],
};

type StoreTablePayloadState = {
  storeFilterPayload: StoreTablePayload;
  setStoreFilterOffset: (offset: number) => void;
  setStoreFilterDateRange: (fromDate: string | null, toDate: string | null) => void;
  setStoreFilterStatus: (status: StoreStatus[]) => void;
  setStoreFilterMaxAmount: (maxAmount: number | null) => void;
  setStoreFilterMinAmount: (minAmount: number | null) => void;
  setStoreFilterSearch: (option: string, value: string | undefined) => void;
  resetStoreFilter: () => void;
  selectedModalStores: StoresType;
  setSelectedModalStores: (stores: StoresType) => void;
  storeGroupInputValue: string;
  setStoreGroupInputValue: (value: string) => void;
  setSelectedStoreIds: (storeIds: string[]) => void;
  // property to use as a trigger for react query to invalidate the cache and refetch the data with default filters
  filtersResetAt: number;
};

export const useStoreTablePayloadStore = create<StoreTablePayloadState>((set) => ({
  storeFilterPayload,
  storeGroupInputValue: 'All Stores',
  filtersResetAt: Date.now(),
  setStoreFilterOffset: (offset) =>
    set((state) => ({ ...state, storeFilterPayload: { ...state.storeFilterPayload, offset } })),
  setStoreFilterDateRange: (fromDate, toDate) =>
    set((state) => ({
      ...state,
      storeFilterPayload: { ...state.storeFilterPayload, fromDate, toDate },
    })),
  setStoreFilterStatus: (status) =>
    set((state) => ({
      ...state,
      storeFilterPayload: { ...state.storeFilterPayload, status: [...status] },
    })),
  setStoreFilterMaxAmount: (maxAmount) =>
    set((state) => ({
      ...state,
      storeFilterPayload: { ...state.storeFilterPayload, maxAmount },
    })),
  setStoreFilterMinAmount: (minAmount) =>
    set((state) => ({
      ...state,
      storeFilterPayload: { ...state.storeFilterPayload, minAmount },
    })),
  setStoreFilterSearch: (option, value) =>
    set((state) => ({
      ...state,
      storeFilterPayload: {
        ...state.storeFilterPayload,
        storeName: storeFilterPayload.storeName,
        storeCode: storeFilterPayload.storeCode,
        [option]: value,
      },
    })),
  resetStoreFilter: () =>
    set((state) => ({
      ...state,
      storeFilterPayload,
      storeGroupInputValue: 'All Stores',
      filtersResetAt: Date.now(),
      selectedModalStores: {},
    })),
  selectedModalStores: {},
  setSelectedModalStores: (stores) => set((state) => ({ ...state, selectedModalStores: stores })),
  setStoreGroupInputValue: (value) => set((state) => ({ ...state, storeGroupInputValue: value })),
  setSelectedStoreIds: (storeIds) =>
    set((state) => ({
      ...state,
      storeFilterPayload: { ...state.storeFilterPayload, storeIds },
      selectedModalStores: {},
    })),
}));
