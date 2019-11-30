import { merchantFetch } from 'merchant/utils/ajax';

import RegistrationLink from 'merchant/models/RegistrationLink';
import { makeEntityReducer } from 'merchant_common/reducers/entity';

const REGISTRATION_LINK_FETCH = 'REGISTRATION_LINK_FETCH';
const REGISTRATION_LINK_CREATE = 'REGISTRATION_LINK_CREATE';
const REGISTRATION_LINK_CANCEL = 'REGISTRATION_LINK_CANCEL';

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

export const cancelRegistrationLink = params => {
  let registrationLink = new RegistrationLink(params);

  return {
    type: REGISTRATION_LINK_CANCEL,
    payload: registrationLink.cancel(),
  };
};

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
  }).then(resp => {
    if (resp.data && resp.data.url) {
      window.location = resp.data.url;

      return;
    }

    throw {
      errors: 'Signed NACH form is not available',
    };
  });
};

export default makeEntityReducer(REGISTRATION_LINK_FETCH);
