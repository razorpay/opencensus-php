import { set } from 'common/utils/immutable';

import {
  fetchStore as fetchStoreApi,
  fetchProducts as fetchProductsApi,
} from 'merchant/views/Stores/model';

// Action types

const FETCH_STORE = 'FETCH_STORE';
const CREATE_STORE = 'CREATE_STORE';
const UPDATE_STORE = 'UPDATE_STORE';
const FETCH_PRODUCTS = 'FETCH_PRODUCTS';
const DELETE_STORE = 'DELETE_STORE';
const HIDE_SUCCESS_MODAL = 'HIDE_SUCCESS_MODAL';
const UPDATE_PRODUCTS_LIST = 'UPDATE_PRODUCTS_LIST';

// Actions creators

export const fetchStore = () => {
  return {
    type: FETCH_STORE,
    payload: fetchStoreApi(),
  };
};

export const createStore = (payload) => {
  return {
    type: CREATE_STORE,
    payload,
  };
};

export const updateStore = (payload) => {
  return {
    type: UPDATE_STORE,
    payload,
  };
};

export const fetchProducts = (params) => {
  return {
    type: FETCH_PRODUCTS,
    payload: fetchProductsApi(params),
  };
};

export const deleteStore = () => {
  return {
    type: DELETE_STORE,
  };
};

export const hideSuccessModal = () => {
  return {
    type: HIDE_SUCCESS_MODAL,
  };
};

export const updateProductsList = (payload) => {
  return {
    type: UPDATE_PRODUCTS_LIST,
    payload,
  };
};

const initialState = {
  products: {
    loading: true,
    items: [],
    error: null,
  },
  orders: {
    loading: true,
    items: [],
    error: null,
  },
  entity: {
    error: '',
    loading: true,
    isSuccessModal: false,
    data: {
      id: '',
      settings: {
        title: '',
        slug: '',
        shipping_fees: '',
        shipping_days: '',
      },
    },
  },
};

const pruneSettings = (data) => {
  if (data) {
    // if object exists, send it
    if (!Array.isArray(data.settings)) {
      return { ...data.settings };
    }
  }
  // if empty array or settings key missing, send empty object
  return {};
};

export default (state = initialState, action) => {
  let index;

  switch (action.type) {
    case HIDE_SUCCESS_MODAL:
      return set(state, 'entity', {
        ...state.entity,
        isSuccessModal: false,
      });

    case CREATE_STORE:
      return set(state, 'entity', {
        ...state.entity,
        isSuccessModal: true,
        data: {
          ...action.payload.data,
          settings: pruneSettings(action.payload.data),
        },
      });

    case UPDATE_STORE:
      return set(state, 'entity', {
        ...state.entity,
        data: { ...action.payload.data },
      });

    case `${FETCH_STORE}::PENDING`:
      return set(state, 'entity', { ...state.entity, loading: true, error: '' });

    case `${FETCH_STORE}::SUCCESS`:
      return set(state, 'entity', {
        ...state.entity,
        loading: false,
        error: '',
        data: { ...action.payload.data, settings: pruneSettings(action.payload.data) },
      });

    case `${FETCH_STORE}::ERROR`:
      return set(state, 'entity', {
        ...state.entity,
        loading: false,
        error: action.payload.errors[0],
      });

    case `${FETCH_PRODUCTS}::SUCCESS`:
      return set(state, 'products', {
        ...state.products,
        loading: false,
        items: action.payload.data,
      });

    case `${DELETE_STORE}`:
      return set(state, 'entity', {
        ...initialState.entity,
        loading: false,
        error: '',
      });

    case UPDATE_PRODUCTS_LIST:
      index = state.products.items.findIndex((product) => product.id === action.payload.id);

      return set(state, `products.items.${index}`, action.payload);

    default:
      return state;
  }
};
