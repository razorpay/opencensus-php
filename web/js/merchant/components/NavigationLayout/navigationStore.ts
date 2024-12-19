import create from 'zustand';
import { SideNavSection } from './SideNavigation/SideNavigation';

interface ProductStore {
  selectedProduct: {};
}

const useConnectedNavigationStore = create<any>((set) => ({
  //Product selection
  selectedProduct: {
    product: null,
  },
  setProduct: (product) =>
    set({
      selectedProduct: {
        product,
      },
    }),
  resetSelectedProduct: () =>
    set({
      selectedProduct: {
        product: null,
      },
    }),
}));

export default useConnectedNavigationStore;
