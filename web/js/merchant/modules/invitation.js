import Invitation from 'merchant/models/Invitation';

const INVITATION_DELETE = 'INVITATION_DELETE';
const INVITATION_EDIT = 'INVITATION_EDIT';
const INVITATION_RESEND = 'INVITATION_RESEND';

export const cancelInvitation = inviteId => ({
  type: INVITATION_DELETE,
  payload: new Invitation({ id: inviteId }).delete({ mode: 'live' }),
});

export const updateInvitation = data => ({
  type: INVITATION_EDIT,
  payload: new Invitation().update(data),
});

export const resendInvitation = data => ({
  type: INVITATION_RESEND,
  payload: new Invitation(data).resend(),
});
