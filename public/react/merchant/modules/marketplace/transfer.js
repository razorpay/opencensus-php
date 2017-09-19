import { set, merge, unshift } from 'rzp/utils/immutable';
import Transfer from 'merchant/models/Transfer';
import { makeEntityReducer } from 'rzp/modules/entity';

const TRANSFER_FETCH = 'TRANSFER_FETCH';
const TRANSFER_REVERSAL = 'TRANSFER_REVERSAL';
const TRANSFER_FETCH_REVERSAL = 'TRANSFER_FETCH_REVERSAL';
const UPDATE_TRANSFER = 'UPDATE_TRANSFER';

export const fetchTransfer = id => {
  let transfer = new Transfer();

  return {
    type: TRANSFER_FETCH,
    payload: transfer.fetch(id, { expand: ['recipient_settlement'] }),
  };
};

export const reverseTransfer = (id, data) => {
  const transfer = new Transfer({ id });

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

export const createTransfer = data => {
  const { id, ...params } = data;
  const transfer = new Transfer(params);

  return transfer.save({ id, ...data });
};

export const updateTransfer = (id, data) => {
  const transfer = new Transfer({ id });

  return transfer.update(data);
};

let defaultInitialState = {
  loading: true,
  error: null,
  entity: {},

  reversals: {
    loading: true,
    items: [],
    error: null,
  },
};

const transferReducer = makeEntityReducer(
  TRANSFER_FETCH,
  {
    [`${TRANSFER_FETCH_REVERSAL}::PENDING`]: (state, action) => {
      return set(state, 'reversals', {
        loading: true,
        items: [],
        error: null,
      });
    },

    [`${TRANSFER_FETCH_REVERSAL}::SUCCESS`]: (state, action) => {
      return set(state, 'reversals', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });
    },

    [`${TRANSFER_FETCH_REVERSAL}::ERROR`]: (state, action) => {
      return set(state, 'reversals', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });
    },
  },
  defaultInitialState
);

export default transferReducer;
