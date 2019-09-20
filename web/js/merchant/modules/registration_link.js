import { merchantFetch } from 'merchant/utils/ajax';

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

export const validateNachFile = (file, id) => {
  let formData = new FormData();
  formData.append('form_uploaded', file);
  formData.append('auth_link_id', id);

  return merchantFetch({
    url: `token.registration/paper_mandate/validate/proxy`,
    method: 'post',
    mode: 'test',
    data: formData,
    headers: {
      'Access-Control-Allow-Origin': '*',
    },
    withCredentials: true,
  });
};

export const authenticateNACHFile = (file, id) => {
  let formData = new FormData();
  formData.append('form_uploaded', file);
  formData.append('auth_link_id', id);

  return merchantFetch({
    url: 'token.registration/paper_mandate/authenticate/proxy',
    method: 'post',
    data: formData,
  });
};

export default makeEntityReducer(REGISTRATION_LINK_FETCH);
