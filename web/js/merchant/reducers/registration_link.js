import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

import RegistrationLink from 'merchant/models/RegistrationLink';

const REGISTRATION_LINK_FETCH = 'REGISTRATION_LINK_FETCH';
const REGISTRATION_LINK_CREATE = 'REGISTRATION_LINK_CREATE';
const REGISTRATION_LINK_CANCEL = 'REGISTRATION_LINK_CANCEL';
const SMS_SEND = 'SMS_SEND';
const EMAIL_SEND = 'EMAIL_SEND';

export const notifyCustomer = (id, type) => {
  return {
    type: type === 'sms' ? SMS_SEND : EMAIL_SEND,
    payload: merchantFetch({
      url: `subscription_registration/auth_links/${id}/notify_by/${type}`,
      method: 'post',
    }),
  };
};

export const fetchRegistrationLink = (id) => ({
  type: REGISTRATION_LINK_FETCH,
  payload: merchantFetch(`subscription_registration/auth_links/${id}/internal`).then(
    (resp) => new RegistrationLink(resp.data),
  ),
});

export const createRegistrationLink = (params) => ({
  type: REGISTRATION_LINK_CREATE,
  payload: new RegistrationLink().save(params),
});

export const cancelRegistrationLink = (params) => {
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

export const downloadSignedNACHFile = (data) => {
  return merchantFetch({
    url: `token.registration/paper_mandate/uploaded_form`,
    data,
  }).then((resp) => {
    if (resp.data && resp.data.url) {
      window.location = resp.data.url;

      return;
    }

    throw {
      errors: 'Signed NACH form is not available',
    };
  });
};

let initialState = {
  loading: true,
  entity: {},
  error: null,
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${REGISTRATION_LINK_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${REGISTRATION_LINK_CANCEL}::SUCCESS`:
    case `${REGISTRATION_LINK_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        entity: action.payload,
        error: null,
      });

    case `${REGISTRATION_LINK_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        entity: initialState.entity,
      });

    case `${SMS_SEND}::SUCCESS`:
      return set(state, 'entity.sms_status', 'sent');

    case `${EMAIL_SEND}::SUCCESS`:
      return set(state, 'entity.email_status', 'sent');

    default:
      return state;
  }
}
