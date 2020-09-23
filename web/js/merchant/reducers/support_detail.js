import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

//supprt detail constant
const FETCH_MERCHANT_SUPPORT_DETAIL = 'FETCH_MERCHANT_SUPPORT_DETAIL';
const ADD_MERCHANT_SUPPORT_DETAIL = 'CREATE_MERCHANT_SUPPORT_DETAIL';

let initialState = {
  merchantSupportDetail: {
    loading: true,
    data: {},
    error: null,
  },
};

//actions
export const fetchSupportDetail = () => {
  return {
    type: FETCH_MERCHANT_SUPPORT_DETAIL,
    payload: merchantFetch('proxy/merchants/supportdetails'),
  };
};

export const createSupportDetail = (data) => {
  return {
    type: ADD_MERCHANT_SUPPORT_DETAIL,
    payload: merchantFetch({
      url: 'proxy/merchants/supportdetails',
      method: 'put',
      data: data,
    }),
  };
};

export const fetchNoOfTransaction = (params) => {
  return merchantFetch({
    url: 'payments',
    mode: 'live',
    method: 'get',
    params,
  });
};

//reducers
export default function (state = initialState, action) {
  switch (action.type) {
    case `${FETCH_MERCHANT_SUPPORT_DETAIL}::SUCCESS`:
    case `${ADD_MERCHANT_SUPPORT_DETAIL}::SUCCESS`:
      return merge(state, {
        merchantSupportDetail: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${FETCH_MERCHANT_SUPPORT_DETAIL}::ERROR`:
    case `${ADD_MERCHANT_SUPPORT_DETAIL}::ERROR`:
      return set(state, 'merchantSupportDetail', {
        data: state.merchantSupportDetail.data,
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
