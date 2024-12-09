import { merge, push, deepMerge } from 'common/utils/immutable';
import { ProductStatusKeys } from 'merchant/views/PaymentPages/common/Products/utils';

import {
  fetchProductCatalogs,
  fetchStorefrontCategories,
  fetchStorefrontEntity,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import { transformStorefront, transformLineItem, transformCatalog } from './transformer';
import { IBannerImage } from 'merchant/reducers/paymentPages/types';

// TODO: Remove dependency from here

export interface ProductImage {
  description?: string;
  id?: string;
  large?: string;
  medium?: string;
  original: string;
  small?: string;
  title?: string;
}

export interface IPaymentPagesProduct {
  id: string;
  product_name: string;
  description: string;
  // string value in rupees (unformatted)
  amount: string;
  // string value in rupees (unformatted)
  discounted_amount: string;
  // string value based on status
  units: string;
  images: Array<ProductImage>;
  // use category id
  category: string | null;
  status: ProductStatusKeys;
}

export interface IPaymentPagesCategory {
  id: string;
  name: string;
  alias: string;
  created_at: number;
}

export interface PaymentPagesStorefrontType {
  id: null | string;
  isLoading: boolean;
  error: string;
  isStorefrontPage: boolean;
  isDesktopPreview: boolean;
  entity: {
    title: string;
    banner_images: Array<IBannerImage>;
    contactPhone: string;
    contactEmail: string;
    terms?: string;
    // to be planned
    products: IPaymentPagesProduct[];
    shortUrl: string;
    expire_by: number | null;
    slug: string | null;
    settings: {
      banner_enabled: boolean;
      payment_success_message?: string;
      payment_success_redirect_url?: string;
      pp_fb_event_add_to_cart_enabled?: string;
      pp_fb_event_initiate_payment_enabled?: string;
      pp_fb_event_payment_complete_enabled?: string;
      pp_fb_pixel_tracking_id?: string;
      pp_ga_pixel_tracking_id?: string;
      enable_custom_serial_number?: string;
      enable_receipt?: string;
    };
  };
  allCategories: {
    data: IPaymentPagesCategory[];
    loading: boolean;
    error: string;
  };
  allProducts: {
    data: IPaymentPagesProduct[];
    loading: boolean;
    error: string;
  };
}
const FETCH_STOREFRONT_ENTITY = 'FETCH_STOREFRONT_ENTITY';
const EDIT_STOREFRONT = 'EDIT_STOREFRONT';
const EDIT_STOREFRONT_DEEP_MERGE = 'EDIT_STOREFRONT_DEEP_MERGE';
const ADD_PRODUCT = 'ADD_PRODUCT';
const ADD_PRODUCTS = 'ADD_PRODUCTS';
const REMOVE_PRODUCT = 'REMOVE_PRODUCT';
const EDIT_PRODUCT = 'EDIT_PRODUCT';
const PP_STOREFRONT_ACTIVE = 'PP_STOREFRONT_ACTIVE';
const PP_FETCH_CATEGORIES = 'PP_FETCH_CATEGORIES';
const PP_FETCH_PRODUCTS = 'PP_FETCH_PRODUCTS';
const ADD_CATEGORIES = 'ADD_CATEGORIES';
const PP_PREVIEW_DEVICE = 'PP_PREVIEW_DEVICE';
const RESET_PP_STOREFRONT = 'RESET_PP_STOREFRONT';

export const fetchStorefront = (id: string, isIntentDuplicate?: boolean) => {
  return {
    type: FETCH_STOREFRONT_ENTITY,
    payload: fetchStorefrontEntity(id),
    isIntentDuplicate,
    id,
  };
};
export const fetchProducts = (count?: number, isIncludeImagesCategories?: boolean): any => {
  return {
    type: PP_FETCH_PRODUCTS,
    payload: fetchProductCatalogs(count, isIncludeImagesCategories),
  };
};

export const fetchCategories = () => {
  return {
    type: PP_FETCH_CATEGORIES,
    payload: fetchStorefrontCategories(),
  };
};

export const editStorefront = (key: string, value: string | boolean) => {
  return {
    type: EDIT_STOREFRONT,
    payload: {
      key,
      value,
    },
  };
};

export const editStorefrontDeepMerge = (payload: any) => ({
  type: EDIT_STOREFRONT_DEEP_MERGE,
  payload,
});

export const addProduct = (payload: any) => ({
  type: ADD_PRODUCT,
  payload,
});

export const addProducts = (payload: any) => ({
  type: ADD_PRODUCTS,
  payload,
});
export const addCategory = (payload: any) => ({
  type: ADD_CATEGORIES,
  payload,
});

export const editProduct = (payload: any) => ({
  type: EDIT_PRODUCT,
  payload,
});

export const removeProduct = (payload: string) => ({
  type: REMOVE_PRODUCT,
  payload,
});

export const previewDevice = (payload: boolean) => ({
  type: PP_PREVIEW_DEVICE,
  payload,
});

export const resetStorefront = (payload: boolean) => ({
  type: RESET_PP_STOREFRONT,
  payload,
});

export const setIsStorefrontPage = (isActive: boolean) => {
  return {
    type: PP_STOREFRONT_ACTIVE,
    payload: isActive,
  };
};

const initialState: PaymentPagesStorefrontType = {
  id: null,
  isLoading: true,
  isStorefrontPage: false,
  error: '',
  isDesktopPreview: true,
  entity: {
    title: '',
    banner_images: [],
    contactPhone: '',
    contactEmail: '',
    terms: '',
    products: [],
    shortUrl: '',
    expire_by: null,
    settings: {
      banner_enabled: true,
    },
    slug: '',
  },
  allCategories: {
    data: [],
    error: '',
    loading: false,
  },
  allProducts: {
    data: [],
    error: '',
    loading: false,
  },
};

export default (state = initialState, action) => {
  switch (action.type) {
    case RESET_PP_STOREFRONT:
      // on the unmount we reset the entity related fields (so that it doesn't have stale data before the next time the component is re-opened)
      return merge(state, {
        ...initialState,
        entity: {
          ...initialState.entity,
        },
        /*
          we are preserving the following fields between multiple storefront creation, as this is common data
          for now its not being used, but ideally we want to avoid hitting allProducts API multiple times, as its a large API call
          Ideally all add/edit product changes should update the allProducts array in redux.
        */
        isStorefrontPage: state.isStorefrontPage,
        allCategories: state.allCategories,
        allProducts: state.allProducts,
      });
    case EDIT_STOREFRONT:
      return merge(state, {
        entity: {
          ...state.entity,
          [action.payload.key]: action.payload.value,
        },
      });

    case EDIT_STOREFRONT_DEEP_MERGE:
      return merge(state, {
        entity: deepMerge(state.entity, action.payload),
      });

    case ADD_PRODUCT:
      return merge(state, {
        entity: {
          ...state.entity,
          products: push(state.entity.products, transformLineItem(action.payload)),
        },
      });

    case ADD_PRODUCTS:
      return merge(state, {
        entity: {
          ...state.entity,
          products: [...state.entity.products, ...action.payload],
        },
      });

    case EDIT_PRODUCT: {
      const transformedProduct = transformLineItem(action.payload);

      const newProducts = state.entity.products.map((item) => {
        // update the product id
        if (item.id === transformedProduct.id) {
          return transformedProduct;
        }
        return item;
      });

      return merge(state, {
        entity: {
          ...state.entity,
          products: newProducts,
        },
      });
    }
    case ADD_CATEGORIES:
      return merge(state, {
        allCategories: {
          ...state.allCategories,
          data: push(state.allCategories.data, action.payload),
        },
      });

    case REMOVE_PRODUCT: {
      const newProducts = state.entity.products.filter((item) => item.id !== action.payload);

      return merge(state, {
        entity: {
          ...state.entity,
          products: newProducts,
        },
      });
    }

    case `${FETCH_STOREFRONT_ENTITY}::PENDING`:
      return merge(state, {
        isLoading: true,
        error: '',
        id: action.id,
      });

    case `${FETCH_STOREFRONT_ENTITY}::SUCCESS`: {
      return merge(state, {
        isLoading: false,
        error: '',
        id: action.payload.id,
        entity: transformStorefront(action.payload.data),
      });
    }

    case `${FETCH_STOREFRONT_ENTITY}::ERROR`:
      return merge(state, {
        isLoading: false,
        error: 'Something went wrong',
        entity: initialState.entity,
      });

    case `${PP_FETCH_CATEGORIES}::PENDING`: {
      return merge(state, {
        allCategories: {
          ...state.allCategories,
          error: '',
          loading: true,
        },
      });
    }
    case `${PP_FETCH_CATEGORIES}::ERROR`: {
      return merge(state, {
        allCategories: {
          ...state.allCategories,
          loading: true,
          // ensure we set error object on if it fails
          error: action.payload.errors ? action.payload.errors : 'Something went wrong',
        },
      });
    }
    case `${PP_FETCH_CATEGORIES}::SUCCESS`: {
      let categories = [];
      if (action.payload?.data?.items) {
        categories = action.payload.data.items;
      }
      return merge(state, {
        allCategories: {
          loading: false,
          data: categories,
        },
      });
    }

    case `${PP_FETCH_PRODUCTS}::PENDING`: {
      return merge(state, {
        allProducts: {
          ...state.allProducts,
          error: '',
          loading: true,
        },
      });
    }
    case `${PP_FETCH_PRODUCTS}::ERROR`: {
      return merge(state, {
        allProducts: {
          ...state.allProducts,
          loading: false,
          data: [],
          // ensure we set error object on if it fails
          error: action.payload.errors ? action.payload.errors : 'Something went wrong',
        },
      });
    }
    case `${PP_FETCH_PRODUCTS}::SUCCESS`: {
      let products = [];
      if (action.payload?.data?.items) {
        // TODO: Test after PR merge
        products = action.payload.data.items.map((item) => transformCatalog(item));
      }
      return merge(state, {
        allProducts: {
          loading: false,
          data: products,
          error: '',
        },
      });
    }

    case 'PP_STOREFRONT_ACTIVE':
      return merge(state, {
        isStorefrontPage: action.payload,
      });

    case PP_PREVIEW_DEVICE:
      return merge(state, {
        isDesktopPreview: action.payload,
      });

    default:
      return state;
  }
};
