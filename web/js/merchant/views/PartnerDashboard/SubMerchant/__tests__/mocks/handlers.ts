import {
  losProductsListHandler,
  capitalApplicationsHandler,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/CapitalClients/__tests__/mocks/once-handlers';
import {
  acceptedInvitesListHandler,
  submerchantDetailsHandler,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';

export const subMerchantListHandlers = [
  acceptedInvitesListHandler(),
  submerchantDetailsHandler(),
  losProductsListHandler(),
  capitalApplicationsHandler(),
];
