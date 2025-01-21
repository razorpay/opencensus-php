import type {
  StoreSearchInput,
  StoreStatus,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

export type DateRangePropsType = {
  selectedDateRange: [string | null, string | null];
  setStoreFilterDateRange: (fromDate: string | null, toDate: string | null) => void;
};

export type StatusPropsType = {
  selectedStoreStatus: StoreStatus[];
  setStoreFilterStatus: (status: StoreStatus[]) => void;
};

export type AmountPropsType = {
  minAmount: number | null;
  maxAmount: number | null;
  setStoreFilterMinAmount: (minAmount: number | null) => void;
  setStoreFilterMaxAmount: (maxAmount: number | null) => void;
};

export type SearchPropsType = {
  searchInputs: StoreSearchInput;
  setStoreFilterSearch: (option: string, value: string | undefined) => void;
};
