import { useStore } from '@federated/apps/shell/commonStore';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled, isInternalTestingEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';

interface RTUXHomepageEnabled {
  user: User;
  abExperiments: any;
}

export const isRTUXHomepageEnabled = ({ user, abExperiments }: RTUXHomepageEnabled): boolean => {
  // Note: when updating the logic for rtux here, please also update the logic in web/js/common/utils/observability.js

  const isInternalTesting = isInternalTestingEnabled(abExperiments);
  if (isInternalTesting) return true;

  const isEligibleForRTUX = user?.isAccepted && user.isCountryIndia && user.isOrgRZP;

  if (!isEligibleForRTUX) return false;

  if (user.isPartner()) return isExperimentEnabled(abExperiments.rtux_homepage_partner);

  if (isExperimentEnabled(abExperiments.enable_rtux_blacklisted_segment)) return true;

  return (
    !checkIfPosSalesAgent({ user, abExperiments })?.isPosSalesAgent &&
    isExperimentEnabled(abExperiments.rtux_homepage)
  );
};

export const isRTUXLeftoutSegmentEnabled = ({ splitz }: RTUXHomepageEnabled): boolean => {
  return isExperimentEnabled(splitz?.abExperiments?.enable_rtux_blacklisted_segment);
};

export const useIsRTUXHomepageEnabled = (): boolean => {
  const user = useStore((state) => state.session.user) as unknown as User;
  const { abExperiments } = useSplitzService();
  return isRTUXHomepageEnabled({ user, abExperiments });
};

export const isDateRangeForInsightChartsEnabled = (abExperiments: any = {}): boolean => {
  return isExperimentEnabled(abExperiments.date_range_insight_charts);
};

export const isOmniHomepageEnabled = (abExperiments: any = {}): boolean => {
  return isExperimentEnabled(abExperiments.omni_homepage_enabled);
};
