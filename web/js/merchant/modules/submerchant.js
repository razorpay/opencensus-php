import Submerchant from 'merchant/models/Submerchant';

import { merge } from 'rzp/utils/immutable';

const SUB_MERCHANT_CREATE = 'SUB_MERCHANT_CREATE';
const SUB_MERCHANT_FETCH_DETAILS = 'SUB_MERCHANT_FETCH_DETAILS';

export const create = payload => {
  return {
    type: SUB_MERCHANT_CREATE,
    payload: new Submerchant().create(payload),
  };
};

export const fetchSubmerchant = submerchantId => {
  return {
    type: SUB_MERCHANT_FETCH_DETAILS,
    payload: new Submerchant().fetch(submerchantId),
  };
};

const initialState = {
  loading: true,
  item: {},
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${SUB_MERCHANT_FETCH_DETAILS}::PENDING`:
      return merge(state, {
        loading: true,
        item: {},
        error: null,
      });

    case `${SUB_MERCHANT_FETCH_DETAILS}::SUCCESS`:
      return merge(state, {
        item: action.payload,
        loading: false,
        error: null,
      });

    case `${SUB_MERCHANT_FETCH_DETAILS}::ERROR`:
      return merge(state, {
        loading: false,
        item: {},
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
