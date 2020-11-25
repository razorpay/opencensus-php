import { set, merge } from 'common/utils/immutable';

import Dispute from 'merchant/models/Dispute';

export const DISPUTES_LOAD = 'DISPUTES_LOAD';
export const FETCH_OPEN = 'FETCH_OPEN_DISPUTES';
export const DISPUTE_FETCH = 'DISPUTE_FETCH';

export const loadDispute = (payload) => {
  let dispute = new Dispute();
  return {
    type: DISPUTE_FETCH,
    payload: dispute.fetch(
      payload.id ? payload.id : payload,
      {},
      { expand: ['transaction.settlement'] },
    ),
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

export default function (state = initialState, action) {
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
