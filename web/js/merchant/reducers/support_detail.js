// eslint-disable-next-line import/no-cycle
import { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

//supprt detail constant
const FETCH_MERCHANT_SUPPORT_DETAIL = 'FETCH_MERCHANT_SUPPORT_DETAIL';
const ADD_MERCHANT_SUPPORT_DETAIL = 'CREATE_MERCHANT_SUPPORT_DETAIL';

const initialState = {
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
      data,
    }),
  };
};

//reducers
export default (state = initialState, action) => {
  switch (action.type) {
    case `${FETCH_MERCHANT_SUPPORT_DETAIL}::SUCCESS`:
    case `${ADD_MERCHANT_SUPPORT_DETAIL}::SUCCESS`:
      return merge(state, {
        merchantSupportDetail: {
          /* 
            Added this alternative support to update the state with initial state value or whatever is previously there 
            cause if there is not support detail entry in the DB for merchant instead of throwing error from now BE will
            send NULL in data object or the response so to accommodate the rest of the code making data a {} (blank object)
          */
          data: action.payload.data || state.merchantSupportDetail.data,
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
};
