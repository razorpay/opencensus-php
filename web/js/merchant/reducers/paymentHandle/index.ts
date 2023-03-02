import { set } from 'common/utils/immutable';

import {
  editSlugApi,
  getSlugSuggestionsApi,
  fetchPaymentHandleApi,
  createPaymentHandleApi,
} from 'merchant/reducers/paymentHandle/api';

interface State {
  handleInfo: {
    data: any;
    error: any;
    loading: boolean;
  };
}

const FETCH_PAYMENT_HANDLE = 'FETCH_PAYMENT_HANDLE';
const CREATE_PAYMENT_HANDLE = 'CREATE_PAYMENT_HANDLE';

const initialState: State = {
  handleInfo: { loading: false, data: {}, error: null },
};

interface FetchPaymentHandleAction {
  type: typeof FETCH_PAYMENT_HANDLE;
  payload: any;
}

interface CreatePaymentHandleAction {
  type: typeof CREATE_PAYMENT_HANDLE;
  payload: any;
}

type PaymentHandleAction = FetchPaymentHandleAction | CreatePaymentHandleAction;

export const fetchPaymentHandle = (): FetchPaymentHandleAction => {
  return {
    type: FETCH_PAYMENT_HANDLE,
    payload: fetchPaymentHandleApi(),
  };
};

export const createPaymentHandle = (): CreatePaymentHandleAction => {
  return {
    type: CREATE_PAYMENT_HANDLE,
    payload: createPaymentHandleApi(),
  };
};

export const getSlugSuggestions = () => {
  return getSlugSuggestionsApi();
};

export const editSlug = (slug: string) => {
  return editSlugApi(slug);
};

export default function paymentHandleReducer(
  state = initialState,
  action: PaymentHandleAction,
): State {
  switch (action.type) {
    case `${FETCH_PAYMENT_HANDLE}::PENDING`:
      return set(state, 'handleInfo', {
        loading: true,
        data: {},
        error: null,
      });

    case `${FETCH_PAYMENT_HANDLE}::ERROR`:
      return set(state, 'handleInfo', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    case `${FETCH_PAYMENT_HANDLE}::SUCCESS`:
      return set(state, 'handleInfo', {
        loading: false,
        data: action.payload.data,
        error: null,
      });

    case `${CREATE_PAYMENT_HANDLE}::PENDING`:
      return set(state, 'handleInfo', {
        loading: true,
        data: {},
        error: null,
      });

    case `${CREATE_PAYMENT_HANDLE}::SUCCESS`:
      return set(state, 'handleInfo', {
        loading: false,
        data: action.payload.data,
        error: null,
      });

    case `${CREATE_PAYMENT_HANDLE}:::ERROR`:
      return set(state, 'handleInfo', {
        loading: false,
        data: {},
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
