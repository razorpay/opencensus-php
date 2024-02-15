import { acceptedInvitesListHandler as pgAcceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';

import { accountsListResponsePOS } from './fixtures';
export const acceptedInvitesListHandlerPOS = (data = accountsListResponsePOS) =>
  pgAcceptedInvitesListHandler(data);
