import { merchantFetch } from 'merchant/utils/ajax';

import RegistrationLink from 'merchant/models/RegistrationLink';
import { makeEntityReducer } from 'rzp/modules/entity';

const REGISTRATION_LINK_FETCH = 'REGISTRATION_LINK_FETCH';
const REGISTRATION_LINK_CREATE = 'REGISTRATION_LINK_CREATE';

export const fetchRegistrationLink = id => ({
  type: REGISTRATION_LINK_FETCH,
  payload: merchantFetch(
    `subscription_registration/auth_links/${id}/internal`
  ).then(resp => resp.data),
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
    data: formData,
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

export const downloadSignedNACHFile = data => {
  return merchantFetch({
    url: `token.registration/paper_mandate/uploaded_form`,
    data,
  }).then(resp => axios(resp.data.url));
};

export default makeEntityReducer(REGISTRATION_LINK_FETCH);
