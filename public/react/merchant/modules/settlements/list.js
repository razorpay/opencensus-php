import Settlement from 'merchant/models/Settlement';
import { set, merge } from 'rzp/utils/immutable';

const SETTLEMENTS_FETCH = 'SETTLEMENTS_FETCH';

export const fetchSettlements = params => {
  return dispatch => {
    let settlement = new Settlement();
    return dispatch({
      type: SETTLEMENTS_FETCH,
      payload: settlement.fetchAll(params),
    });
  };
};

let initialState = {
  loading: true,
  settlements: [],
  count: 0,
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${SETTLEMENTS_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${SETTLEMENTS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        settlements: action.payload.data.items,
        count: action.payload.data.count,
        error: null,
      });

    case `${SETTLEMENTS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
