import { GetFiltersType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import { commonFilterInputs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';

const {
  accountIdField,
  activationStatusField,
  appIdField,
  contactMobileField,
  contactInfoField,
  emailField,
  nameField,
} = commonFilterInputs;

export const getFiltersList: GetFiltersType<'user'> = ({ user }) => {
  const isCombinedContactFilterEnabled = user.isOrgRZP;
  const mobileOrContactField = isCombinedContactFilterEnabled
    ? contactInfoField
    : contactMobileField;
  const conditionalActivationStatusFilter = isCombinedContactFilterEnabled
    ? [activationStatusField]
    : [];
  const conditionalEmailField = !isCombinedContactFilterEnabled ? [emailField] : [];
  const conditionalAppIdFilter = user.isPartner('pure_platform') ? [appIdField] : [];

  return [
    nameField,
    accountIdField,
    mobileOrContactField,
    ...conditionalAppIdFilter,
    ...conditionalActivationStatusFilter,
    ...conditionalEmailField,
  ];
};
