import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

/**
 * This hook is used to check if the 2FA experiment is enabled or not
 * when changing the password or generating/re-generating the API keys
 */
export default function useTrigger2Fa() {
  const { abExperiments } = useSplitzService();

  return isExperimentEnabled(abExperiments.enable_2fa_password_api_keys);
}
