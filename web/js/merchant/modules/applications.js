import { set, merge, remove } from 'rzp/utils/immutable';
import Application from 'merchant/models/Application';
import { merchantFetch } from 'rzp/utils/ajax';

const FETCH_APPLICATIONS = 'FETCH_APPLICATIONS';
const FETCH_CONNECTED_APPLICATIONS = 'FETCH_CONNECTED_APPLICATIONS';
const FETCH_APPLICATION_DETAILS = 'FETCH_APPLICATION_DETAILS';
const CREATE_APPLICATION = 'CREATE_APPLICATION';
const UPDATE_APPLICATION = 'UPDATE_APPLICATION';
const DELETE_APPLICATION = 'DELETE_APPLICATION';
const REVOKE_ACCESS_TOKEN = 'REVOKE_ACCESS_TOKEN';

export const fetchWebhook = id => {
  console.log(id);
  return merchantFetch(`webhooks/${id}`);
};

export const createAppWebhook = (appId, data) => {
  let keepKeys = ['url', 'secret', 'events']; // Send only these fields in EDIT mode
  const payload = {
    url: data.url,
    secret: data.secret,
    events: {},
  };

  for (let k in data.events) {
    if (data.events.hasOwnProperty(k)) {
      payload.events[k] = data.events[k] ? 1 : 0;
    }
  }
  return merchantFetch({
    url: `oauth/applications/${appId}/webhooks`,
    method: 'post',
    data: payload,
  });
};

export const editAppWebhook = params => {
  console.log(params);
  return merchantFetch({
    url: `webhooks/${params.id}`,
    method: 'post',
    data,
  });
};

export const fetchApplications = params => {
  let application = new Application();

  return {
    type: FETCH_APPLICATIONS,
    payload: application.fetchAll(params),
  };
};

export const fetchConnectedApplications = params => {
  let application = new Application();

  return {
    type: FETCH_CONNECTED_APPLICATIONS,
    payload: application.fetchConnected(params),
  };
};

export const fetchApplication = params => {
  let application = new Application();

  return {
    type: FETCH_APPLICATION_DETAILS,
    payload: application.fetch(params),
  };
};

export const deleteApplication = id => {
  let application = new Application({ id });

  return {
    type: DELETE_APPLICATION,
    payload: application.delete(id),
  };
};

export const revokeAccess = id => {
  let application = new Application({ id });

  return {
    type: REVOKE_ACCESS_TOKEN,
    payload: application.revokeToken(id),
  };
};

export const createApplication = (params, fileName) => {
  let application = new Application();

  return {
    type: CREATE_APPLICATION,
    payload: application.create(params, fileName),
  };
};
export const updateApplication = (id, params, fileName) => {
  let application = new Application({ id });

  return {
    type: UPDATE_APPLICATION,
    payload: application.update(params, fileName),
  };
};

let initialState = {
  loading: true,
  createdAppsloading: true,
  connectedAppsloading: true,
  items: [],
  details: {},
  tokens: [],
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${CREATE_APPLICATION}::PENDING`:
    case `${UPDATE_APPLICATION}::PENDING`:
    case `${DELETE_APPLICATION}::PENDING`:
    case `${REVOKE_ACCESS_TOKEN}::PENDING`:
    case `${FETCH_APPLICATION_DETAILS}::PENDING`:
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
      });

    case `${CREATE_APPLICATION}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: [action.payload],
      });

    case `${DELETE_APPLICATION}::SUCCESS`:
      return merge(state, {
        items: remove(state.items, item => item.id === action.payload.id),
        loading: false,
      });

    case `${REVOKE_ACCESS_TOKEN}::SUCCESS`:
      return merge(state, {
        tokens: remove(state.tokens, item => item.id === action.payload.id),
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
