import AuthLink from 'merchant/models/AuthLink';

import { makeEntityReducer } from 'rzp/modules/entity';

const AUTH_LINK_FETCH = 'AUTH_LINK_FETCH';

export const fetchAuthLink = id => ({
  type: AUTH_LINK_FETCH,
  payload: new AuthLink().fetch(id),
});

export default makeEntityReducer(AUTH_LINK_FETCH);
