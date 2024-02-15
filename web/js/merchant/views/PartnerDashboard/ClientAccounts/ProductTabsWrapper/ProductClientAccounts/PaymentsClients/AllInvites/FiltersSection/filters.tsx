import { GetFiltersTypeNoArgs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import { commonFilterInputs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';

const { nameField, emailField, phoneNumberField, countField } = commonFilterInputs;
export const getFiltersList: GetFiltersTypeNoArgs = () => {
  return [nameField, emailField, phoneNumberField, countField];
};
