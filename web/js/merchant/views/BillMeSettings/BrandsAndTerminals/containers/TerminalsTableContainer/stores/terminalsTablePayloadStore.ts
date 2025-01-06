import create from 'zustand';

import type {
  BillingTerminalsType,
  TerminalStatusOptionsType,
  TerminalSearchColumnType,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/types';
import type { PaginationLimitType } from 'merchant/views/BillMeSettings/common/types';

export type TerminalsTablePayload = {
  limit: PaginationLimitType;
  offset: number;
  searchTerm: string | undefined;
  isActive: TerminalStatusOptionsType;
  type: BillingTerminalsType;
  searchColumn: TerminalSearchColumnType;
};

const terminalsFilterPayload: TerminalsTablePayload = {
  limit: 10,
  offset: 0,
  searchTerm: '',
  isActive: 'ALL',
  type: 'BILLING',
  searchColumn: 'TERMINAL_NAME',
};

type TerminalsTablePayloadState = {
  terminalsFilterPayload: TerminalsTablePayload;
  setTerminalsFilterLimit: (limit: PaginationLimitType) => void;
  setTerminalsFilterOffset: (offset: number) => void;
  setTerminalsFilterSearch: (searchTerm: string | undefined) => void;
  setTerminalsFilterStatus: (isActive: TerminalStatusOptionsType) => void;
  setTerminalsSearchByColumn: (searchColumn: TerminalSearchColumnType) => void;
  resetTerminalsPayloadFilters: () => void;
};

export const useTerminalsTablePayloadStore = create<TerminalsTablePayloadState>((set) => ({
  terminalsFilterPayload,
  setTerminalsFilterOffset: (offset) =>
    set((state) => ({
      ...state,
      terminalsFilterPayload: { ...state.terminalsFilterPayload, offset },
    })),
  setTerminalsFilterLimit: (limit) =>
    set((state) => ({
      ...state,
      terminalsFilterPayload: { ...state.terminalsFilterPayload, offset: 0, limit },
    })),
  setTerminalsFilterSearch: (searchTerm) =>
    set((state) => ({
      ...state,
      terminalsFilterPayload: { ...state.terminalsFilterPayload, searchTerm },
    })),
  setTerminalsFilterStatus: (isActive) =>
    set((state) => ({
      ...state,
      terminalsFilterPayload: { ...state.terminalsFilterPayload, isActive },
    })),
  setTerminalsSearchByColumn: (searchColumn) =>
    set((state) => ({
      ...state,
      terminalsFilterPayload: { ...state.terminalsFilterPayload, searchColumn },
    })),
  resetTerminalsPayloadFilters: () =>
    set((state) => ({
      ...state,
      terminalsFilterPayload,
    })),
}));
