import create from 'zustand';

import { FetchProductsRequest, Filters } from './types';

type RizeMarketplaceStore = Pick<FetchProductsRequest, 'search'> & {
  filters: Filters;
  isDealAvailed: boolean;
  setCategory: (category: Filters['category']) => void;
  setSearch: (search: FetchProductsRequest['search']) => void;
  setIsDealAvailed: (isDealAvailed: boolean) => void;
};

const useRizeMarketplaceStore = create<RizeMarketplaceStore>((set) => ({
  filters: {
    category: [],
  },
  search: '',
  isDealAvailed: false,
  setCategory(category) {
    set({ filters: { category } });
  },
  setSearch(search) {
    set({ search });
  },
  setIsDealAvailed(isDealAvailed) {
    set({ isDealAvailed });
  },
}));

export { useRizeMarketplaceStore };
