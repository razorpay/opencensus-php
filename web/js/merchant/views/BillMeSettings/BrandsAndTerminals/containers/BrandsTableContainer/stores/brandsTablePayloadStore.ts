import create from 'zustand';

import type { PaginationLimitType } from 'merchant/views/BillMeSettings/common/types';
import { BrandSearchColumnType } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandsTablePayload = {
  limit: PaginationLimitType;
  offset: number;
  searchTerm: string | undefined;
  searchColumn: BrandSearchColumnType;
};

const brandsFilterPayload: BrandsTablePayload = {
  limit: 10,
  offset: 0,
  searchTerm: '',
  searchColumn: 'BRAND_NAME',
};

type BrandsTablePayloadState = {
  brandsFilterPayload: BrandsTablePayload;
  setBrandsFilterLimit: (limit: PaginationLimitType) => void;
  setBrandsFilterOffset: (offset: number) => void;
  setBrandsFilterSearch: (searchTerm: string | undefined) => void;
  resetBrandsPayloadFilters: () => void;
};

export const useBrandsTablePayloadStore = create<BrandsTablePayloadState>((set) => ({
  brandsFilterPayload,
  setBrandsFilterOffset: (offset) =>
    set((state) => ({ ...state, brandsFilterPayload: { ...state.brandsFilterPayload, offset } })),
  setBrandsFilterLimit: (limit) =>
    set((state) => ({
      ...state,
      brandsFilterPayload: { ...state.brandsFilterPayload, offset: 0, limit },
    })),
  setBrandsFilterSearch: (searchTerm) =>
    set((state) => ({
      ...state,
      brandsFilterPayload: { ...state.brandsFilterPayload, searchTerm },
    })),
  resetBrandsPayloadFilters: () =>
    set((state) => ({
      ...state,
      brandsFilterPayload,
    })),
}));
