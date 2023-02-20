import { set, unshift } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

import {
  IPaymentPagesProduct,
  IPaymentPagesCategory,
} from 'merchant/reducers/paymentPages/storefront';

export interface PaymentPagesProductsPage {
  products: {
    items: IPaymentPagesProduct[];
    loading: boolean;
    error: string;
  };
  categories: {
    items: IPaymentPagesCategory[];
    loading: boolean;
    error: string;
  };
}

const PP_PRODUCTS_FETCH = 'PP_PRODUCTS_FETCH';
const PP_CATEGORIES_FETCH = 'PP_CATEGORIES_FETCH';
const PP_PRODUCTS_PRODUCT_LIST_UPDATE = 'PP_PRODUCTS_PRODUCT_LIST_UPDATE';
const PP_PRODUCTS_DELETE_PRODUCT = 'PP_PRODUCTS_DELETE_PRODUCT';
const PP_PRODUCTS_CATEGORY_LIST_UPDATE = 'PP_PRODUCTS_CATEGORY_LIST_UPDATE';
const PP_PRODUCTS_CATEGORY_LIST_ADD = 'PP_PRODUCTS_CATEGORY_LIST_ADD';
const PP_PRODUCTS_DELETE_CATEGORY = 'PP_PRODUCTS_DELETE_CATEGORY';

export const fetchProducts = (params: any) => {
  return {
    type: PP_PRODUCTS_FETCH,
    payload: merchantFetch({
      url: 'stores/catalogs',
      method: 'get',
      data: {
        includes: 'Images,Categories',
        ...params,
      },
    }),
  };
};

export const fetchCategories = () => {
  return {
    type: PP_CATEGORIES_FETCH,
    payload: merchantFetch({
      url: 'stores/categories',
      method: 'get',
      data: {
        count: 500,
        includes: 'catalog_count',
      },
    }),
  };
};

export const updateProductsList = (product: any) => {
  return {
    type: PP_PRODUCTS_PRODUCT_LIST_UPDATE,
    payload: product,
  };
};

export const deleteProductFromList = (id: string) => {
  return {
    type: PP_PRODUCTS_DELETE_PRODUCT,
    payload: id,
  };
};

export const updateCategoriesList = (category: any) => {
  return {
    type: PP_PRODUCTS_CATEGORY_LIST_UPDATE,
    payload: category,
  };
};

export const addInCategoriesList = (category: any) => {
  return {
    type: PP_PRODUCTS_CATEGORY_LIST_ADD,
    payload: category,
  };
};

export const deleteCategoryFromList = (id: string) => {
  return {
    type: PP_PRODUCTS_DELETE_CATEGORY,
    payload: id,
  };
};

const initialState: PaymentPagesProductsPage = {
  products: {
    loading: false,
    items: [],
    error: '',
  },
  categories: {
    items: [],
    loading: false,
    error: '',
  },
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${PP_PRODUCTS_FETCH}::PENDING`:
      return set(state, 'products', {
        ...state.products,
        loading: true,
        items: [],
        error: '',
      });

    case `${PP_PRODUCTS_FETCH}::SUCCESS`: {
      return set(state, 'products', {
        ...state.products,
        loading: false,
        items: action.payload.data.items,
      });
    }

    case `${PP_PRODUCTS_FETCH}::ERROR`:
      return set(state, 'products', {
        ...state.products,
        loading: false,
        error: action.error,
      });

    case `${PP_CATEGORIES_FETCH}::PENDING`:
      return set(state, 'categories', {
        ...state.categories,
        loading: true,
        items: [],
        error: '',
      });

    case `${PP_CATEGORIES_FETCH}::SUCCESS`: {
      return set(state, 'categories', {
        ...state.categories,
        loading: false,
        items: action.payload.data.items,
      });
    }

    case `${PP_CATEGORIES_FETCH}::ERROR`:
      return set(state, 'categories', {
        ...state.categories,
        loading: false,
        error: action.error,
      });

    case 'PP_PRODUCTS_PRODUCT_LIST_UPDATE': {
      const updatedProductIndex = state.products.items.findIndex(
        (product) => product.id === action.payload.id,
      );

      if (updatedProductIndex === -1) return state;

      return set(state, `products.items.${updatedProductIndex}`, action.payload);
    }

    case 'PP_PRODUCTS_DELETE_PRODUCT': {
      const updatedList = state.products.items.filter((product) => action.payload !== product.id);

      return set(state, 'products', {
        ...state.products,
        loading: false,
        items: updatedList,
      });
    }

    case 'PP_PRODUCTS_CATEGORY_LIST_UPDATE': {
      const updatedCategoryIndex = state.categories.items.findIndex(
        (category) => category.id === action.payload.id,
      );

      if (updatedCategoryIndex === -1) return state;

      return set(state, `categories.items.${updatedCategoryIndex}`, action.payload);
    }

    case 'PP_PRODUCTS_CATEGORY_LIST_ADD': {
      return set(state, `categories.items`, unshift(state.categories.items, action.payload));
    }

    case 'PP_PRODUCTS_DELETE_CATEGORY': {
      const updatedList = state.categories.items.filter(
        (category) => action.payload !== category.id,
      );

      return set(state, 'categories', {
        ...state.categories,
        items: updatedList,
      });
    }

    default:
      return state;
  }
};
