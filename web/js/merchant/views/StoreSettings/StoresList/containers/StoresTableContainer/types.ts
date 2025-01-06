import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import {
  LINKED_PRODUCTS_MAP,
  STORES_SEARCH_BY_FIELDS,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/constants';

export type StoreType = keyof typeof STORE_TYPE_MAP;
export type StoreLinkedProductsType = keyof typeof LINKED_PRODUCTS_MAP;
export type StoreSearchColumnType = keyof typeof STORES_SEARCH_BY_FIELDS;

type StoreInfo = {
  storeCode: string;
  storeType: StoreType;
  linkedProducts: StoreLinkedProductsType[];
};

export type Store = {
  id: string;
  name: string;
  storeInfo: StoreInfo;
  dates: {
    deletedAt: string | null;
  };
};

export type StoresDataResponse = {
  stores: {
    stores: Store[];
    limit: number;
    offset: number;
    total: number;
  };
};
