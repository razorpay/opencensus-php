import request from 'rzp/utils/request';
import { set, merge, unshift, remove } from 'rzp/utils/immutable';
import { merchantFetch } from 'rzp/utils/ajax';

export const TEAM_FETCH = 'TEAM_FETCH';
export const INVITATION_SEND = 'INVITATION_SEND';
export const INVITATION_RESEND = 'INVITATION_RESEND';
export const INVITATION_UPDATE = 'INVITATION_UPDATE';
export const INVITATION_REMOVE = 'INVITATION_REMOVE';
export const USER_UPDATE = 'USER_UPDATE';
export const USER_REMOVE = 'USER_REMOVE';

const fetchInvitations = _ => merchantFetch('invitations');

const fetchUsers = _ => merchantFetch(`merchants/users`);

export const fetchTeamDetails = params => {
  return {
    type: TEAM_FETCH,
    payload: Promise.all([fetchInvitations(), fetchUsers()]).then(values => {
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
    payload: merchantFetch({
      url: 'invitations',
      method: 'post',
      data,
    }),
  };
};

export const resendInvitation = (inviteId, data) => {
  return {
    type: INVITATION_RESEND,
    payload: merchantFetch({
      url: `invitations/${inviteId}/resend`,
      method: 'put',
      data,
    }),
  };
};

export const updateInvitation = (inviteId, data) => {
  return {
    type: INVITATION_UPDATE,
    payload: merchantFetch({
      url: `invitations/${inviteId}`,
      method: 'patch',
      data,
    }),
  };
};

export const cancelInvitation = inviteId => {
  return {
    type: INVITATION_REMOVE,
    payload: merchantFetch({
      method: 'delete',
      url: `invitations/${inviteId}`,
    }),
  };
};

export const updateUser = (userId, data) => {
  return {
    type: USER_UPDATE,
    payload: merchantFetch({
      url: `users/${userId}/update`,
      method: 'put',
      data,
    }),
  };
};

export const removeUser = userId => {
  return {
    type: USER_REMOVE,
    payload: merchantFetch({
      method: 'put',
      url: `users/${userId}/detach`,
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
