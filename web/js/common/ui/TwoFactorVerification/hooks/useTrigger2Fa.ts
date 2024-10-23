import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

/**
 * This hook is used to check if the 2FA experiment is enabled when
 * changing the password, generating/re-generating the API keys,
 * adding account or inviting new member flow.
 */
export default function useTrigger2Fa() {
  const { abExperiments } = useSplitzService();

  return isExperimentEnabled(abExperiments.enable_2fa_for_protected_flows);
}
