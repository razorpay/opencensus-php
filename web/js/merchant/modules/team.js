import { set, merge, unshift, remove } from 'rzp/utils/immutable';
import defaultAjax, { merchantFetch } from 'merchant/utils/ajax';

import Team from 'merchant/models/Team';

export const TEAM_FETCH = 'TEAM_FETCH';
export const INVITATION_SEND = 'INVITATION_SEND';
export const INVITATION_RESEND = 'INVITATION_RESEND';
export const INVITATION_UPDATE = 'INVITATION_UPDATE';
export const INVITATION_REMOVE = 'INVITATION_REMOVE';
export const USER_UPDATE = 'USER_UPDATE';
export const USER_REMOVE = 'USER_REMOVE';

const TEAM_MEMBER_DELETE = 'TEAM_MEMBER_DELETE';
const TEAM_MEMBER_EDIT = 'TEAM_MEMBER_EDIT';
const TEAM_MEMBER_CREATE = 'TEAM_MEMBER_CREATE';

const fetchInvitations = _ =>
  merchantFetch({
    url: 'invitations',
    mode: 'live',
  });

const fetchUsers = _ =>
  merchantFetch({
    url: 'merchants-users',
    mode: 'live',
  });

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

export const resendInvitation = (inviteId, data) => {
  return {
    type: INVITATION_RESEND,
    payload: merchantFetch({
      url: `invitations/${inviteId}/resend`,
      method: 'put',
      mode: 'live',
      data,
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
      mode: 'live',
    }),
  };
};

export const cancelInvitation = inviteId => {
  const team = new Team();
  return {
    type: TEAM_MEMBER_DELETE,
    payload: team.cancelInvitation(inviteId),
  };
};

export const sendInvitation = data => {
  const team = new Team();
  return {
    type: TEAM_MEMBER_CREATE,
    payload: team.sendInvitation(data),
  };
};

export const updateInvitation = data => ({
  type: TEAM_MEMBER_EDIT,
  payload: new Team().updateInvitation(data),
});

export const updateMember = data => {
  const team = new Team();
  return {
    type: TEAM_MEMBER_EDIT,
    payload: team.updateMember(data),
  };
};

export const removeUser = userId => {
  const team = new Team();
  return {
    type: TEAM_MEMBER_DELETE,
    payload: team.deleteMember(userId),
  };
};

export const toggle2FaEnforcement = flag => {
  return {
    type: USER_UPDATE,
    payload: defaultAjax(`/merchants/2fa`, {
      method: 'patch',
      appendModeInURL: false,
      data: {
        second_factor_auth: flag,
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
