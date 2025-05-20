import { isBrowser } from '../../server/utils';

export const isOneHomeExperimentEnabled = () => {
  if (isBrowser()) {
    return window?.IS_ONE_HOME_ENABLED;
  }
  return false;
};

export const isCompanyRegistrationExperimentEnabled = () => {
  if (isBrowser()) {
    return window?.IS_COMPANY_REGISTRATION_TOP_NAV_ENABLED;
  }
  return false;
};
