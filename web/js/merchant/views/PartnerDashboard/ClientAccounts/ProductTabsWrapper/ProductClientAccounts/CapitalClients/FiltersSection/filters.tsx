import { GetFiltersTypeNoArgs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import { commonFilterInputs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';

const { accountIdField, countField, emailField, accountNameField } = commonFilterInputs;

export const getFiltersList: GetFiltersTypeNoArgs = () => [
  accountNameField,
  accountIdField,
  emailField,
  countField,
];
