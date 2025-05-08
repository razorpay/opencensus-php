import { Product } from '@dashboards/payments/views/POS/types';
import create from 'zustand';
import { devtools } from 'zustand/middleware';
import { type ProductAlias } from '../../components/Navigation/types';

type SelectedProduct = {
  title: string;
  alias: ProductAlias;
};

type StoreState = {
  products: {
    selectedProduct: SelectedProduct | null;
  };
  setSelectedProduct: (payload: SelectedProduct | null) => void;
  updateSelectedProduct: (payload: SelectedProduct) => void;
  isSideNavOpenOnMobile: boolean;
  setIsSideNavOpenOnMobile: (payload: boolean) => void;
  showSearchOnMobile: boolean;
  setShowSearchOnMobile: (payload: boolean) => void;
};

const initialState = {
  products: {
    selectedProduct: null as StoreState['products']['selectedProduct'],
  } as StoreState['products'],
  isSideNavOpenOnMobile: false,
  showSearchOnMobile: true,
};

export const useConnectedNavigationStore = create<StoreState>(
  devtools(
    (set) => ({
      ...initialState,
      setSelectedProduct: (payload) => {
        set((state) => ({
          products: {
            ...state.products,
            selectedProduct: payload,
          },
        }));
      },
      updateSelectedProduct: (payload) => {
        set((state) => ({
          products: {
            ...state.products,
            selectedProduct: {
              ...state.products.selectedProduct,
              ...payload,
            },
          },
        }));
      },
      setIsSideNavOpenOnMobile: (payload) => {
        set((state) => ({
          isSideNavOpenOnMobile: payload,
        }));
      },
      setShowSearchOnMobile: (payload) => {
        set((state) => ({
          showSearchOnMobile: payload,
        }));
      },
    }),
    'Connected Navigation Store',
  ),
);
