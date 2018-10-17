import Token from 'merchant/models/Token';

import { makeEntityReducer } from 'rzp/modules/entity';

const TOKEN_FETCH = 'TOKEN_FETCH';

export const fetchToken = id => ({
  type: TOKEN_FETCH,
  payload: new Token().fetch(id),
});

export default makeEntityReducer(TOKEN_FETCH);
