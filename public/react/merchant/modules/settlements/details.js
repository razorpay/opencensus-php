import Settlement from 'merchant/models/Settlement';
import { set, merge } from 'rzp/utils/immutable';

const SETTLEMENT_FETCH = 'SETTLEMENT_FETCH';
const SETTLEMENT_BREAKUP_FETCH = 'SETTLEMENT_BREAKUP_FETCH';

export const fetchItem = id => {
  let settlement = new Settlement();

  return {
    type: SETTLEMENT_FETCH,
    payload: settlement.fetch(id),
  };
};

export const fetchBreakupDetails = params => {
  let settlement = new Settlement(params);
  return {
    type: SETTLEMENT_BREAKUP_FETCH,
    payload: settlement.fetchBreakupDetails(),
  };
};

let initialState = {
  loading: true,
  settlement: {},
  error: null,
  breakupDetails: {
    loading: false,
    items: [],
    error: null,
  },
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${SETTLEMENT_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${SETTLEMENT_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        settlement: action.payload,
        error: null,
      });

    case `${SETTLEMENT_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        settlement: initialState.settlement,
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::PENDING`:
      return set(state, 'breakupDetails', {
        loading: true,
        items: [],
        error: null,
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::SUCCESS`:
      return set(state, 'breakupDetails', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });

    case `${SETTLEMENT_BREAKUP_FETCH}::ERROR`:
      return set(state, 'breakupDetails', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
