import request from 'rzp/utils/request';
import { set, merge, unshift, remove } from 'rzp/utils/immutable';

export const TEAM_FETCH = 'TEAM_FETCH';
export const INVITATION_SEND = 'INVITATION_SEND';
export const INVITATION_RESEND = 'INVITATION_RESEND';
export const INVITATION_UPDATE = 'INVITATION_UPDATE';
export const INVITATION_REMOVE = 'INVITATION_REMOVE';
export const USER_UPDATE = 'USER_UPDATE';
export const USER_REMOVE = 'USER_REMOVE';

const GENERIC_URL = process.env.RZP_ADMIN ? '/admin/generic' : '/user/generic';

const fetchInvitations = merchant_id => {
  var params = {
    route_name: 'invitation_fetch',
  };
  if (process.env.RZP_ADMIN) {
    params.merchant_id = merchant_id;
  }

  return request(GENERIC_URL, {
    queryParams: params,
    appendModeInURL: false,
  });
};

const fetchUsers = merchant_id => {
  var params = {
    route_name: 'merchant_fetch_users',
    url_params: JSON.stringify({
      '{id}': merchant_id,
    }),
  };

  return request(GENERIC_URL, {
    queryParams: params,
    appendModeInURL: false,
  });
};

export const fetchTeamDetails = params => {
  return {
    type: TEAM_FETCH,
    payload: Promise.all([
      fetchInvitations(params.merchant_id),
      fetchUsers(params.merchant_id),
    ]).then(values => {
      if (!values[0].success || !values[1].success) {
        throw "Couldn't load team details";
      }
      return values;
    }),
  };
};

export const sendInvitation = data => {
  return {
    type: INVITATION_SEND,
    payload: request(GENERIC_URL, {
      method: 'post',
      appendModeInURL: false,
      body: {
        route_name: 'invitation_create',
        body: data,
      },
    }),
  };
};

export const resendInvitation = (inviteId, data) => {
  return {
    type: INVITATION_RESEND,
    payload: request(GENERIC_URL, {
      method: 'put',
      appendModeInURL: false,
      body: {
        route_name: 'invitation_resend',
        url_params: JSON.stringify({
          '{id}': inviteId,
        }),
        body: data,
      },
    }),
  };
};

export const updateInvitation = (inviteId, data) => {
  return {
    type: INVITATION_UPDATE,
    payload: request(GENERIC_URL, {
      method: 'patch',
      appendModeInURL: false,
      body: {
        route_name: 'invitation_edit',
        url_params: JSON.stringify({
          '{id}': inviteId,
        }),
        body: data,
      },
    }),
  };
};

export const cancelInvitation = inviteId => {
  return {
    type: INVITATION_REMOVE,
    payload: request(GENERIC_URL, {
      method: 'delete',
      appendModeInURL: false,
      body: {
        route_name: 'invitation_delete',
        url_params: JSON.stringify({
          '{id}': inviteId,
        }),
      },
    }),
  };
};

export const updateUser = (userId, body) => {
  return {
    type: USER_UPDATE,
    payload: request(GENERIC_URL, {
      method: 'put',
      appendModeInURL: false,
      body: {
        route_name: 'user_merchant_mapping_action',
        url_params: JSON.stringify({
          '{id}': userId,
          '{action}': 'update',
        }),
        body: body,
      },
    }),
  };
};

export const removeUser = userId => {
  return {
    type: USER_REMOVE,
    payload: request(GENERIC_URL, {
      method: 'put',
      appendModeInURL: false,
      body: {
        route_name: 'user_merchant_mapping_action',
        url_params: JSON.stringify({
          '{id}': userId,
          '{action}': 'detach',
        }),
      },
    }),
  };
};

let initialState = {
  loading: true,
  invitations: [],
  users: [],
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${TEAM_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${TEAM_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        invitations: action.payload[0].data,
        users: action.payload[1].data,
      });

    case `${TEAM_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    default:
      return state;
  }
}
