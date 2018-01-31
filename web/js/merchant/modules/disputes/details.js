import { set, merge } from 'rzp/utils/immutable';

import Dispute from 'merchant/models/Dispute';

export const DISPUTES_LOAD = 'DISPUTES_LOAD';
export const FETCH_OPEN = 'FETCH_OPEN_DISPUTES';

export const loadDispute = dispute => {
  return {
    type: DISPUTES_LOAD,
    payload: dispute,
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
        item: action.payload,
      });

    case `${FETCH_OPEN}::SUCCESS`:
      return merge(state, {
        openDisputes: action.payload,
      });

    default:
      return state;
  }
}
