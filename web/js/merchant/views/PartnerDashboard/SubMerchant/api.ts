import { merchantFetch } from 'merchant/utils/ajax';

export const createSubmerchantInvite = (params) => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Create',
    mode: 'live',
    method: 'post',
    data: { invite: params },
  });
};
