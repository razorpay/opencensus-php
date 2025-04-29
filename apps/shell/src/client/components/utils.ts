import { isBrowser } from '../../server/utils';

export const isOneHomeExperimentEnabled = () => {
  if (isBrowser()) {
    return window?.IS_ONE_HOME_ENABLED;
  }
  return false;
};
