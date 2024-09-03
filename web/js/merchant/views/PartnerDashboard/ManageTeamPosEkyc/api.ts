import Invitation from 'merchant/models/Invitation';
import Team from 'merchant/models/Team';
import { merchantFetch } from 'merchant/utils/ajax';

import { SendInviteApiT, ResendSmsApiT } from './types';

export const sendInviteApi = (data: SendInviteApiT) => {
  //TODO: name and team lead are missing in api call
  return merchantFetch({
    url: 'invitations',
    mode: 'live',
    method: 'post',
    data: {
      contact_mobile: `${data.dialCode}${data.contactMobile}`,
      role: data.role,
      sender_name: data.senderName,
      email: '', //temp BE issue, need to pass empty email for now
      metadata: {
        name: data.name,
      },
    },
  });
};

export const resendSmsApi = (data: ResendSmsApiT) => {
  return merchantFetch({
    url: `invitations/${data.id}/resend`,
    mode: 'live',
    method: 'put',
    data: {
      sender_name: data.senderName,
    },
  });
};

export const deleteInvitationApi = (data) => {
  const invitationModel = new Invitation({ id: data.id });
  return invitationModel.delete({ mode: 'live' });
};

export const deleteMemberApi = (data) => {
  const teamModel = new Team();
  return teamModel.deleteMember(data.id);
};

export const updateMemberApi = (data) => {
  const invitationModel = new Invitation({ id: data.id });
  return invitationModel.update({ role: data.role, id: data.id, metadata: { name: data.name } });
};
