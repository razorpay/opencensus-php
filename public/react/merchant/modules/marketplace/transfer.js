import { set, merge, unshift } from 'rzp/utils/immutable';
import Transfer from 'merchant/models/Transfer';

const TRANSFER_FETCH = 'TRANSFER_FETCH';

export const fetchTransfer = id => {
  let transfer = new Transfer();

  return {
    type: TRANSFER_FETCH,
    payload: transfer.fetch(id),
  };
};

let initialState = {
  loading: true,
  error: null,
  entity: {},
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${TRANSFER_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${TRANSFER_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        entity: action.payload,
        error: null,
      });

    case `${TRANSFER_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
        entity: initialState.accounts,
      });

    default:
      return state;
  }
}
