import { CheckboxGroupProps } from '@razorpay/blade/components';

import type { StoreSearchColumnType } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';

type OptionType = {
  label: string;
  value: string;
};

type StatesAndCitiesType = {
  cities: string[];
  states: string[];
};

type Filters = {
  states: StatesAndCitiesType['states'];
  cities: StatesAndCitiesType['cities'];
  searchTerm: string;
  searchColumn: StoreSearchColumnType;
  offset: number;
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

type StoresDataResponse = {
  stores: {
    stores: Store[];
    limit: number;
    offset: number;
    total: number;
  };
};

type StoresType = {
  [key: string]: OptionType;
};

type StoresStatesAndCitiesDataResponse = {
  storesStatesAndCitiesByMerchantId: StatesAndCitiesType;
};

type MultiSelectSlotProps = {
  title: string;
  options: OptionType[];
  onChange?: CheckboxGroupProps['onChange'];
  minExpandedElements?: number;
  name: string;
};

type StoreFilterProps = {
  onSelectStores: (stores: StoresType) => void;
  selectedStores: StoresType;
};

export {
  OptionType,
  Filters,
  StoresDataResponse,
  StoresType,
  Store,
  StoresStatesAndCitiesDataResponse,
  MultiSelectSlotProps,
  StatesAndCitiesType,
  StoreFilterProps,
};
