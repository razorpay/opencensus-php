import { GetFiltersType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import { commonFilterInputs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';

const { accountIdField, countField, emailField, nameField, appIdField } = commonFilterInputs;

export const getFiltersList: GetFiltersType<'user'> = ({ user }) => {
  const conditionalAppIdFilter = user.isPartner('pure_platform') ? [appIdField] : [];

  return [nameField, accountIdField, ...conditionalAppIdFilter, emailField, countField];
};
