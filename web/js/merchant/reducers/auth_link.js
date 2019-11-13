import AuthLink from 'merchant/models/AuthLink';

import { makeEntityReducer } from 'merchant_common/reducers/entity';

const AUTH_LINK_FETCH = 'AUTH_LINK_FETCH';
const AUTH_LINK_CREATE = 'AUTH_LINK_CREATE';

export const fetchAuthLink = id => ({
  type: AUTH_LINK_FETCH,
  payload: new AuthLink().fetch(id),
});

export const createAuthLink = params => ({
  type: AUTH_LINK_CREATE,
  payload: new AuthLink().save(params),
});

export default makeEntityReducer(AUTH_LINK_FETCH);
