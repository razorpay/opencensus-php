import { set, merge } from 'rzp/utils/immutable';
import State from 'merchant/models/State';

const STATES_FETCH = 'STATES_FETCH';

/**
 * Fetches all the states.
 * @param {Object} params
 * @return {Object}
 */
export const fetchStates = params => {
  let states = new State();
  return {
    type: STATES_FETCH,
    payload: states.fetchAll(params),
  };
};

let initialState = {
  loading: true,
  states: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${STATES_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${STATES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        states: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${STATES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    default:
      return state;
  }
}
