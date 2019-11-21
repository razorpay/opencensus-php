import { merchantFetch } from 'merchant/utils/ajax';

import Token from 'merchant/models/Token';

import { makeEntityReducer } from 'rzp/modules/entity';

const TOKEN_FETCH = 'TOKEN_FETCH';
const DELETE_TOKEN = 'TOKEN_DELETE';
const TOKEN_CHARGE = 'TOKEN_CHARGE';

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

export const resubmitNACHFile = id => {
  return merchantFetch({
    url: `token.registration/paper_mandate/token/${id}/retry`,
    method: 'post',
  });
};

export default makeEntityReducer(TOKEN_FETCH);
