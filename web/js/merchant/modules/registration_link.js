import RegistrationLink from 'merchant/models/RegistrationLink';

import { makeEntityReducer } from 'rzp/modules/entity';

const REGISTRATION_LINK_FETCH = 'REGISTRATION_LINK_FETCH';
const REGISTRATION_LINK_CREATE = 'REGISTRATION_LINK_CREATE';

export const fetchRegistrationLink = id => ({
  type: REGISTRATION_LINK_FETCH,
  payload: new RegistrationLink().fetch(id),
});

export const createRegistrationLink = params => ({
  type: REGISTRATION_LINK_CREATE,
  payload: new RegistrationLink().save(params),
});

export default makeEntityReducer(REGISTRATION_LINK_FETCH);
