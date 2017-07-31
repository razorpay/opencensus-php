import { set, merge, unshift } from 'rzp/utils/immutable';
import Transfer from 'merchant/models/Transfer';

const TRANSFER_FETCH = 'TRANSFER_FETCH';
const TRANSFER_REVERSAL = 'TRANSFER_REVERSAL';
const TRANSFER_FETCH_REVERSAL = 'TRANSFER_FETCH_REVERSAL';

export const fetchTransfer = id => {
  let transfer = new Transfer();

  return {
    type: TRANSFER_FETCH,
    payload: transfer.fetch(id),
  };
};

export const reverseTransfer = (id, data) => {
  const transfer = new Transfer({ id });
  console.log(data);

  return {
    type: TRANSFER_REVERSAL,
    payload: transfer.reverse(data),
  };
};

export const fetchReversals = id => {
  const transfer = new Transfer({ id });

  return {
    type: TRANSFER_FETCH_REVERSAL,
    payload: transfer.fetchReversals(),
  };
};

let initialState = {
  loading: true,
  error: null,
  entity: {},

  reversals: {
    loading: true,
    items: [],
    error: null,
  },
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${TRANSFER_REVERSAL}::SUCCESS`:

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

    case `${TRANSFER_FETCH_REVERSAL}::PENDING`:
      return set(state, 'reversals', {
        loading: true,
        items: [],
        error: null,
      });

    case `${TRANSFER_FETCH_REVERSAL}::SUCCESS`:
      return set(state, 'reversals', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });

    case `${TRANSFER_FETCH_REVERSAL}::ERROR`:
      return set(state, 'reversals', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
