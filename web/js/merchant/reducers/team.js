import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import { makeActionCollectionReducer, fetchAll } from 'merchant/reducers/collection';

import Team from 'merchant/models/Team';

export const TEAM_FETCH = 'TEAM_FETCH';
export const INVITATION_SEND = 'INVITATION_SEND';
export const INVITATION_RESEND = 'INVITATION_RESEND';
export const INVITATION_UPDATE = 'INVITATION_UPDATE';
export const INVITATION_REMOVE = 'INVITATION_REMOVE';
export const USER_UPDATE = 'USER_UPDATE';
export const OWNER_UPDATE = 'OWNER_UPDATE';
export const NEW_EMAIL_STATUS = 'NEW_EMAIL_STATUS';
export const USER_REMOVE = 'USER_REMOVE';
export const UPDATE_SESSION = 'UPDATE_SESSION';

const TEAM_MEMBER_DELETE = 'TEAM_MEMBER_DELETE';
const TEAM_MEMBER_UNLOCK = 'TEAM_MEMBER_UNLOCK';
const TEAM_MEMBER_EDIT = 'TEAM_MEMBER_EDIT';
const TEAM_MEMBER_CONTACT_UNVERIFY = 'TEAM_MEMBER_CONTACT_UNVERIFY';

const fetchInvitations = (_) =>
  merchantFetch({
    url: 'invitations',
    mode: 'live',
  });

const fetchUsers = (_) =>
  merchantFetch({
    url: 'merchants-users',
    mode: 'live',
  });

export const fetchTeamDetails = () => {
  return {
    type: TEAM_FETCH,
    payload: Promise.all([fetchInvitations(), fetchUsers()]).then((values) => {
      if (!values[0].success || !values[1].success) {
        throw new Error("Couldn't load team details");
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

export const updateOwner = (email, reattach, setContactEmail = false) => {
  return {
    type: OWNER_UPDATE,
    payload: merchantFetch({
      url: 'merchants/email/update',
      method: 'put',
      data: { email, set_contact_email: setContactEmail, reattach_current_owner: reattach },
    }),
  };
};

export const getEmailStatus = (email, setContactEmail) => {
  return {
    type: NEW_EMAIL_STATUS,
    payload: merchantFetch({
      url: 'merchants/email_user/status',
      method: 'post',
      data: { email, set_contact_email: +setContactEmail, reattach_current_owner: false },
    }),
  };
};

export const updateSelfContact = (data) => {
  return {
    type: UPDATE_SESSION,
    payload: merchantFetch({
      url: `users/contact/update`,
      method: 'patch',
      data,
      mode: 'live',
    }),
  };
};

export const updateMember = (data) => {
  const updateTeam = new Team();
  return {
    type: TEAM_MEMBER_EDIT,
    payload: updateTeam.updateMember(data),
  };
};

export const removeMember = (userId) => {
  const removeTeam = new Team();
  return {
    type: TEAM_MEMBER_DELETE,
    payload: removeTeam.deleteMember(userId),
  };
};

export const unlockMember = (memberId) => ({
  type: TEAM_MEMBER_UNLOCK,
  payload: new Team().unlock(memberId),
});

export const unverifyContact = (memberId) => ({
  type: TEAM_MEMBER_CONTACT_UNVERIFY,
  payload: new Team().unverifyContact(memberId),
});

const toggle2FaEnforcement = (data, url) => {
  return {
    type: UPDATE_SESSION,
    payload: merchantFetch({
      url,
      method: 'patch',
      data,
      headers: {
        'Content-Type': 'application/json',
      },
      should_sync: 1,
    }),
  };
};

export const toggleMerchant2FaEnforcement = (data) => toggle2FaEnforcement(data, 'merchants/2fa');

export const toggleUser2FaEnforcement = (data) => toggle2FaEnforcement(data, 'users/2fa');

const initialState = {
  loading: true,
  invitations: [],
  users: [],
  error: null,
};

export const fetchTeam = (params) => fetchAll(params, Team, 'TEAM_MEMBERS');

export const teamReducer = makeActionCollectionReducer('TEAM_MEMBERS', {
  // since update account api does not send all the details in the response
  'TEAM_MEMBER_CONTACT_UNVERIFY::SUCCESS': (state, action) =>
    updateTeamMember(state, action, '2fa_invalidate'),
  'TEAM_MEMBER_UNLOCK::SUCCESS': (state, action) =>
    updateTeamMember(state, action, 'account_unlock'),

  'TEAM_MEMBER_EDIT::SUCCESS': (state, action) => ({
    ...state,
    items: state.items.map((item) =>
      item.id === action.payload.id
        ? {
            ...item,
            ...action.payload,
          }
        : { ...item },
    ),
  }),
});

function updateTeamMember(state, action, operation) {
  const itemIndex = state.items.findIndex((item) => item.id === action.payload.user_id);

  let key = '';
  let value = '';
  switch (operation) {
    case '2fa_invalidate':
      key = 'contact_mobile_verified';
      value = false;
      break;
    case 'account_unlock':
      key = 'account_locked';
      value = false;
      break;
    default:
  }

  return set(state, `items.${itemIndex}.${key}`, value);
}

export default function team(state = initialState, action) {
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
