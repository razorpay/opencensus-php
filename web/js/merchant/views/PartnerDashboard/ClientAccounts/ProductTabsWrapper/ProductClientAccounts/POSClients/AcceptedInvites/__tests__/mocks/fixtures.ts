import {
  accountsListResponse as pgAccountsListResponse,
  emptyAccountsListResponse,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';

export const emptyAccountsListResponsePOS = emptyAccountsListResponse;
const { items } = pgAccountsListResponse;

const getPosAdditionalData = (name) => ({
  success: true,
  activation_status: 'under_review',
  last_kyc_performed_by: {
    name,
    contact_email: 'kmk@rzp.com',
    contact_no: '8143639996',
    type: 'admin',
    id: `Jz9cQaFeNKHvGA-${name}`,
  },
});
const acceptedInvitesPOS = [
  { ...items[0], pos: getPosAdditionalData('Agent Name 1') },
  { ...items[1], pos: getPosAdditionalData('Client') },
  { ...items[2], pos: getPosAdditionalData('Partner') },
];

export const accountsListResponsePOS = {
  entity: 'collection',
  items: acceptedInvitesPOS,
  count: acceptedInvitesPOS.length,
  offset: '0',
};
