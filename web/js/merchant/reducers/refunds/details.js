import Refund from 'merchant/models/Refund';
import { set, merge } from 'common/utils/immutable';

const REFUND_FETCH = 'REFUND_FETCH';

export const fetchItem = (id) => {
  const refund = new Refund();

  return {
    type: REFUND_FETCH,
    payload: refund.fetch(id, {}, {}),
  };
};

const initialState = {
  loading: true,
  refund: {
    notes: {},
  },
  error: null,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${REFUND_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${REFUND_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        refund: action.payload,
        error: null,
      });

    case `${REFUND_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        refund: initialState.refund,
      });

    default:
      return state;
  }
};
