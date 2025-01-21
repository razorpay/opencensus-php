import { STORES_SEARCH_BY_FIELDS } from '@apps/digital-bills/src/common/components/StoreFilterModal/constants';

type StoreSearchColumnType = keyof typeof STORES_SEARCH_BY_FIELDS;

type SlotOptionType = {
  label: string;
  value: string;
};

type Filters = {
  cities: string[];
  states: string[];
  searchTerm: string;
  searchColumn: StoreSearchColumnType;
  brands: string[];
};

type Store = {
  id: string;
  name: string;
  dates: {
    deletedAt: string;
  };
  storeInfo: {
    storeCode: string;
  };
};

type Brand = {
  id: string;
  name: string;
  dates: {
    deletedAt: string;
  };
};

type StoresDataResponse = {
  stores: {
    stores: Store[];
    limit: number;
    offset: number;
    total: number;
  };
};

type StoresType = {
  [key: string]: SlotOptionType;
};

type StoresStatesAndCitiesDataResponse = {
  storesStatesAndCitiesByMerchantId: {
    states: string[];
    cities: string[];
  };
};

type BrandsDataResponse = {
  storeBrands: {
    storeBrands: Brand[];
    limit: number;
    offset: number;
    total: number;
  };
};

export {
  StoreSearchColumnType,
  SlotOptionType,
  Filters,
  StoresDataResponse,
  StoresType,
  Store,
  StoresStatesAndCitiesDataResponse,
  BrandsDataResponse,
};
