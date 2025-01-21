import create from 'zustand';

import { pastDate, currDate } from '@apps/digital-bills/src/utils/helpers/getDateRangeFromInterval';
import { DurationRange } from '@apps/digital-bills/src/utils/constants';

import type { StoresType } from '@apps/digital-bills/src/common/components/StoreFilterModal/types';
import type {
  BillUserSearchInput,
  TransactionType,
  BillStatus,
  BillsTablePayload,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

const billsFilterPayload: BillsTablePayload = {
  limit: 10,
  offset: 0,
  transactionType: [],
  status: [],
  fromDate: pastDate(DurationRange.Last30Days),
  toDate: currDate(),
  minAmount: null,
  maxAmount: null,
  invoiceNumber: '',
  storeCode: '',
  user: {
    email: '',
    contact: '',
  },
  storeIds: [],
};

type BillsTablePayloadState = {
  billsFilterPayload: BillsTablePayload;
  setBillsFilterOffset: (offset: number) => void;
  setBillsStoreIds: (storeIds: string[]) => void;
  setBillsFilterDateRange: (fromDate: string | null, toDate: string | null) => void;
  setBillsFilterStatus: (status: BillStatus[]) => void;
  setBillsFilterTransaction: (transactionType: TransactionType[]) => void;
  setBillsFilterMaxAmount: (maxAmount: number | null) => void;
  setBillsFilterMinAmount: (minAmount: number | null) => void;
  setBillsFilterSearchByUser: (
    option: keyof BillUserSearchInput,
    value: string | undefined,
  ) => void;
  setBillsFilterSearch: (option: string, value: string | undefined) => void;
  resetBillsFilter: () => void;
  isStoreFilterModalOpen: boolean;
  setStoreFilterModalOpen: (isOpen: boolean) => void;
  storeGroupInputValue: string;
  setStoreGroupInputValue: (value: string) => void;
  setSelectedModalStores: (stores: StoresType) => void;
  selectedModalStores: StoresType;
  // property to use as a trigger for react query to invalidate the cache and refetch the data with default filters
  filtersResetAt: number;
};

const isStoreFilterModalOpen = false;
const storeGroupInputValue = 'All Stores';

export const useBillsTablePayloadStore = create<BillsTablePayloadState>((set) => ({
  billsFilterPayload,
  isStoreFilterModalOpen,
  filtersResetAt: Date.now(),
  setBillsFilterOffset: (offset) =>
    set((state) => ({ ...state, billsFilterPayload: { ...state.billsFilterPayload, offset } })),
  setBillsFilterDateRange: (fromDate, toDate) =>
    set((state) => ({
      ...state,
      billsFilterPayload: { ...state.billsFilterPayload, fromDate, toDate },
    })),
  setBillsFilterStatus: (status) =>
    set((state) => ({
      ...state,
      billsFilterPayload: { ...state.billsFilterPayload, status: [...status] },
    })),
  setBillsFilterTransaction: (transactionType) =>
    set((state) => ({
      ...state,
      billsFilterPayload: { ...state.billsFilterPayload, transactionType: [...transactionType] },
    })),
  setBillsStoreIds: (storeIds) =>
    set((state) => ({
      ...state,
      billsFilterPayload: { ...state.billsFilterPayload, storeIds },
      selectedModalStores: {},
    })),
  setBillsFilterMaxAmount: (maxAmount) =>
    set((state) => ({
      ...state,
      billsFilterPayload: { ...state.billsFilterPayload, maxAmount },
    })),
  setBillsFilterMinAmount: (minAmount) =>
    set((state) => ({
      ...state,
      billsFilterPayload: { ...state.billsFilterPayload, minAmount },
    })),
  setBillsFilterSearchByUser: (option, value) =>
    set((state) => {
      const user = { ...billsFilterPayload.user };
      user[option] = value;
      return {
        ...state,
        billsFilterPayload: {
          ...state.billsFilterPayload,
          invoiceNumber: billsFilterPayload.invoiceNumber,
          storeCode: billsFilterPayload.storeCode,
          user,
        },
      };
    }),
  setBillsFilterSearch: (option, value) =>
    set((state) => ({
      ...state,
      billsFilterPayload: {
        ...state.billsFilterPayload,
        invoiceNumber: billsFilterPayload.invoiceNumber,
        storeCode: billsFilterPayload.storeCode,
        user: { ...billsFilterPayload.user },
        [option]: value,
      },
    })),
  resetBillsFilter: () =>
    set((state) => ({
      ...state,
      billsFilterPayload,
      storeGroupInputValue: 'All Stores',
      filtersResetAt: Date.now(),
      selectedModalStores: {},
    })),

  setStoreFilterModalOpen: (isOpen) =>
    set((state) => ({ ...state, isStoreFilterModalOpen: isOpen })),
  setStoreGroupInputValue: (value) => set((state) => ({ ...state, storeGroupInputValue: value })),
  storeGroupInputValue,
  selectedModalStores: {},
  setSelectedModalStores: (stores) => set((state) => ({ ...state, selectedModalStores: stores })),
}));
