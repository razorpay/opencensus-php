import { set, merge } from 'rzp/utils/immutable';

import Dispute from 'merchant/models/Dispute';

export const DISPUTES_LOAD = 'DISPUTES_LOAD';
export const FETCH_OPEN = 'FETCH_OPEN_DISPUTES';
export const DISPUTE_FETCH = 'DISPUTE_FETCH';

export const loadDispute = payload => {
  const isDispute = typeof payload !== 'string';
  return {
    type: isDispute ? DISPUTES_LOAD : DISPUTE_FETCH,
    payload: isDispute ? payload : new Dispute().fetch(payload),
  };
};

export const fetchOpen = () => {
  const dispute = new Dispute();
  return {
    type: FETCH_OPEN,
    payload: dispute.fetchOpen(),
  };
};

const initialState = {
  loading: true,
  item: {},
  error: null,
  openDisputes: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case DISPUTES_LOAD:
      return merge(state, {
        loading: false,
        item: action.payload,
      });

    case `${DISPUTE_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${DISPUTE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        item: action.payload,
      });

    case `${DISPUTE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors[0],
      });

    case `${FETCH_OPEN}::SUCCESS`:
      return merge(state, {
        openDisputes: action.payload,
      });

    default:
      return state;
  }
}
