import ajax from 'merchant/utils/ajax';
import { set, merge, unshift, remove } from 'rzp/utils/immutable';

const TEAM_FETCH = 'TEAM_FETCH';
const INVITATION_SEND = 'INVITATION_SEND';
const INVITATION_RESEND = 'INVITATION_RESEND';
const INVITATION_UPDATE = 'INVITATION_UPDATE';
const INVITATION_REMOVE = 'INVITATION_REMOVE';
const USER_UPDATE = 'USER_UPDATE';
const USER_REMOVE = 'USER_REMOVE';

export const fetchTeamDetails = params => {
  return {
    type: TEAM_FETCH,
    payload: ajax({
      url: '/settings/merchants/owned',
      appendModeInURL: false,
    }),
  };
};

export const sendInvitation = data => {
  return {
    type: INVITATION_SEND,
    payload: ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: {
        route_name: 'invitation_create',
        body: data,
      },
    }),
  };
};

export const resendInvitation = (inviteId, data) => {
  return {
    type: INVITATION_RESEND,
    payload: ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: {
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
    payload: ajax({
      url: '/user/generic',
      method: 'patch',
      appendModeInURL: false,
      data: {
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
    payload: ajax({
      url: '/user/generic',
      method: 'delete',
      appendModeInURL: false,
      data: {
        route_name: 'invitation_delete',
        url_params: JSON.stringify({
          '{id}': inviteId,
        }),
      },
    }),
  };
};

export const updateUser = (userId, data) => {
  return {
    type: USER_UPDATE,
    payload: ajax({
      url: `/settings/merchants/owned/members/${userId}`,
      method: 'put',
      appendModeInURL: false,
      data,
    }),
  };
};

export const removeUser = userId => {
  return {
    type: USER_REMOVE,
    payload: ajax({
      url: `/settings/merchants/owned/members/${userId}`,
      method: 'delete',
      appendModeInURL: false,
    }),
  };
};

let initialState = {
  loading: true,
  team: {
    invitations: [],
    users: [],
  },
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${TEAM_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${TEAM_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        team: action.payload.data,
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
