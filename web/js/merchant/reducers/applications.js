import { merge, remove } from 'common/utils/immutable';
import Application from 'merchant/models/Application';
import { merchantFetch } from 'merchant/utils/ajax';

const FETCH_APPLICATIONS = 'FETCH_APPLICATIONS';
const FETCH_PARTNER_APPLICATION = 'FETCH_PARTNER_APPLICATION';
const FETCH_CONNECTED_APPLICATIONS = 'FETCH_CONNECTED_APPLICATIONS';
const FETCH_APPLICATION_DETAILS = 'FETCH_APPLICATION_DETAILS';
const CREATE_APPLICATION = 'CREATE_APPLICATION';
const UPDATE_APPLICATION = 'UPDATE_APPLICATION';
const DELETE_APPLICATION = 'DELETE_APPLICATION';
const REVOKE_ACCESS_TOKEN = 'REVOKE_ACCESS_TOKEN';

export const fetchAppWebhooks = (appId, mode) => {
  return merchantFetch({
    url: 'webhooks',
    params: {
      application_id: appId,
    },
    mode,
  });
};

const _makeWebhookPayload = (data, appId) => {
  const payload = {
    url: data.url,
    secret: data.secret,
    events: {},
    application_id: appId,
  };

  if (appId) {
    payload.application_id = appId;
  }

  if (data.alert_email) {
    payload.alert_email = data.alert_email;
  }

  for (const k in data.events) {
    if (data.events.hasOwnProperty(k)) {
      payload.events[k] = data.events[k] ? 1 : 0;
    }
  }

  return payload;
};

// mode is explicitly sent by partner->settings->webhook
export const createAppWebhook = ({ appId, data, mode }) => {
  const payload = _makeWebhookPayload(data, appId);

  return merchantFetch({
    url: `oauth/applications/${appId}/webhooks`,
    method: 'post',
    data: payload,
    mode,
  });
};

// mode is explicitly sent by partner->settings->webhook
export const editAppWebhook = ({ appId, data, mode }) => {
  const payload = _makeWebhookPayload(data, appId);
  payload.active = data.active ? 1 : 0; // Send active field also in edit mode

  return merchantFetch({
    url: `webhooks/${data.id}`,
    method: 'put',
    data: payload,
    mode,
  });
};

export const fetchApplications = (params) => {
  const application = new Application();

  return {
    type: FETCH_APPLICATIONS,
    payload: application.fetchAll(params),
  };
};

export const fetchPartnerApplication = () => {
  const application = new Application();
  return {
    type: FETCH_PARTNER_APPLICATION,
    payload: application.fetchPartnerApplication(),
  };
};

export const fetchConnectedApplications = (params) => {
  const application = new Application();

  return {
    type: FETCH_CONNECTED_APPLICATIONS,
    payload: application.fetchConnected(params),
  };
};

// This is the new endpoint to get the application data
// Re-using same redux action as there is no difference in the data
export const fetchOauthConnectedApplications = () => {
  const application = new Application();

  return {
    type: FETCH_CONNECTED_APPLICATIONS,
    payload: application.fetchOauthConnectedApplications(),
  };
};

// This is the new endpoint to revoke application access
// Re-using same redux action as there is no difference in the data
export const revokeOauthApplicationAccess = (id) => {
  const application = new Application({ id });

  return {
    type: REVOKE_ACCESS_TOKEN,
    payload: application.revokeOauthApplicationAccess(id),
  };
};

export const fetchApplication = (params) => {
  const application = new Application();

  return {
    type: FETCH_APPLICATION_DETAILS,
    payload: application.fetch(params),
  };
};

export const deleteApplication = (id) => {
  const application = new Application({ id });

  return {
    type: DELETE_APPLICATION,
    payload: application.delete(id),
  };
};

export const revokeAccess = (id) => {
  const application = new Application({ id });

  return {
    type: REVOKE_ACCESS_TOKEN,
    payload: application.revokeToken(id),
  };
};

export const createApplication = (params, fileName) => {
  const application = new Application();

  return {
    type: CREATE_APPLICATION,
    payload: application.create(params, fileName),
  };
};
export const updateApplication = (id, params, fileName) => {
  const application = new Application({ id });

  return {
    type: UPDATE_APPLICATION,
    payload: application.update(params, fileName),
  };
};

const initialState = {
  loading: true,
  createdAppsloading: true,
  connectedAppsloading: true,
  items: [],
  details: {},
  tokens: [],
  partnerApplication: {},
  hasConnectedApplications: false,
};

export default function applicationReducer(state = initialState, action) {
  switch (action.type) {
    case `${CREATE_APPLICATION}::PENDING`:
    case `${UPDATE_APPLICATION}::PENDING`:
    case `${DELETE_APPLICATION}::PENDING`:
    case `${REVOKE_ACCESS_TOKEN}::PENDING`:
    case `${FETCH_APPLICATION_DETAILS}::PENDING`:
    case `${FETCH_PARTNER_APPLICATION}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${FETCH_CONNECTED_APPLICATIONS}::PENDING`:
      return merge(state, {
        connectedAppsloading: true,
      });
    case `${FETCH_APPLICATIONS}::PENDING`:
      return merge(state, {
        createdAppsloading: true,
      });

    case `${FETCH_APPLICATIONS}::SUCCESS`:
      return merge(state, {
        createdAppsloading: false,
        items: action.payload.data.items,
      });

    case `${FETCH_CONNECTED_APPLICATIONS}::SUCCESS`:
      return merge(state, {
        connectedAppsloading: false,
        tokens: action.payload.data.items,
        hasConnectedApplications: action.payload.data.items.length > 0,
      });

    case `${FETCH_PARTNER_APPLICATION}::SUCCESS`:
      return merge(state, {
        loading: false,
        partnerApplication: {
          id: action.payload.id,
          clientCredentials: action.payload.client_details,
        },
      });

    case `${CREATE_APPLICATION}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: [action.payload],
      });

    case `${DELETE_APPLICATION}::SUCCESS`:
      return merge(state, {
        items: remove(state.items, (item) => item.id === action.payload.id),
        loading: false,
      });

    case `${REVOKE_ACCESS_TOKEN}::SUCCESS`:
      return merge(state, {
        tokens: remove(
          state.tokens,
          (item) => item.id ?? item.application_id === action.payload.id,
        ),
        loading: false,
      });

    case `${FETCH_APPLICATION_DETAILS}::SUCCESS`:
      return merge(state, {
        loading: false,
      });

    case `${UPDATE_APPLICATION}::SUCCESS`:
      return merge(state, {
        loading: false,
      });
    default:
      return state;
  }
}
