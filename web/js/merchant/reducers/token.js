import { merchantFetch } from 'merchant/utils/ajax';

import Token from 'merchant/models/Token';

import { makeEntityReducer } from 'merchant_common/reducers/entity';

const TOKEN_FETCH = 'TOKEN_FETCH';
const DELETE_TOKEN = 'TOKEN_DELETE';
const TOKEN_CHARGE = 'TOKEN_CHARGE';
const CANCEL_TOKEN = 'CANCEL_TOKEN';

export const fetchToken = id => ({
  type: TOKEN_FETCH,
  payload: new Token().fetch(id),
});

export const chargeToken = ({ id, ...data }) => ({
  type: TOKEN_CHARGE,
  payload: new Token({ id }).chargeToken(data),
});

export const deleteToken = id => ({
  type: DELETE_TOKEN,
  payload: new Token({ id }).delete(),
});

export const cancelToken = (customer_id, token_id) => ({
  type: CANCEL_TOKEN,
  payload: new Token().cancel(customer_id, token_id),
});

export const resubmitNACHFile = id => {
  return merchantFetch({
    url: `token.registration/paper_mandate/token/${id}/retry`,
    method: 'post',
  });
};

export default makeEntityReducer(TOKEN_FETCH, {
  [`${CANCEL_TOKEN}::SUCCESS`]: state => {
    return {
      ...state,
      entity: {
        ...state.entity,
        recurring_details: {
          ...state.entity.recurring_details,
          status: 'cancelled',
        },
      },
    };
  },
});
