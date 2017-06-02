import Payment from 'merchant/models/Payment';
import { set, merge } from 'rzp/utils/immutable';

const PAYMENTS_FETCH = 'PAYMENTS_FETCH';

export const fetchAll = params => {
  return dispatch => {
    let payment = new Payment();
    return dispatch({
      type: PAYMENTS_FETCH,
      payload: payment.fetchAll(params),
    });
  };
};

let initialState = {
  loading: true,
  items: [],
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${PAYMENTS_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${PAYMENTS_FETCH}::SUCCESS`:
      let { items } = action.payload.data;
      return merge(state, {
        loading: false,
        payments: action.payload.data.items,
        items,
        error: null,
      });

    case `${PAYMENTS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
