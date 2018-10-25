import Token from 'merchant/models/Token';

import { makeEntityReducer } from 'rzp/modules/entity';

const TOKEN_FETCH = 'TOKEN_FETCH';
const DELETE_TOKEN = 'TOKEN_DELETE';

export const fetchToken = id => ({
  type: TOKEN_FETCH,
  payload: new Token().fetch(id),
});

export const chargeToken = data => ({
  type: 'CHARGE_TOKEN',
  payload: new Token().chargeToken(data),
});

export const deleteToken = id => ({
  type: DELETE_TOKEN,
  payload: new Token({ id }).delete(),
});

export default makeEntityReducer(TOKEN_FETCH);
