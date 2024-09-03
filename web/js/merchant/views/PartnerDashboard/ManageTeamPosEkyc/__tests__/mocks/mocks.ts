import rolesList from 'merchant/helpers/permissions/roles-list';

export const MOCK_INVITATION_A = {
  id: 'mockInvitationIdA',
  metadata: {
    name: 'mockNameA',
  },
  contact_mobile: '+919234567890',
  role: rolesList.PARTNER_AGENT,
};

export const MOCK_INVITATION_B = {
  id: 'mockInvitationIdB',
  metadata: {
    name: 'mockNameB',
  },
  contact_mobile: '+912223334441',
  role: rolesList.PARTNER_AGENT,
};

export const invitationsMock = [MOCK_INVITATION_A, MOCK_INVITATION_B];

export const MOCK_USER_C = {
  metadata: {
    name: 'Mock username C',
  },
  contact_mobile: '+917778889990',
  id: 'mockIdC',
  role: rolesList.PARTNER_AGENT,
};

export const MOCK_USER_D = {
  metadata: {
    name: 'Mock username D',
  },
  contact_mobile: '+914445556662',
  id: 'mockIdD',
  role: rolesList.PARTNER_AGENT,
};

export const confirmedUsersMock = [MOCK_USER_C, MOCK_USER_D];

export const createInviteMock = {
  metadata: {
    name: 'create invite username E',
  },
  id: 'createInviteId',
  contact_mobile: '8889992221',
  role: rolesList.PARTNER_AGENT,
};
