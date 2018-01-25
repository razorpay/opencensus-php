import { set, merge } from 'rzp/utils/immutable';

import Dispute from 'merchant/models/Dispute';

export const DISPUTES_LOAD = 'DISPUTES_LOAD';

export const loadDispute = dispute => {
  return {
    type: DISPUTES_LOAD,
    payload: dispute,
  };
};

const initialState = {
  loading: true,
  item: {},
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case DISPUTES_LOAD:
      return merge(state, {
        item: action.payload,
      });

    default:
      return state;
  }
}
