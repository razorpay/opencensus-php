import { useStore } from 'shell/commonStore';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled, isInternalTestingEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';

interface RTUXHomepageEnabled {
  user: User;
  abExperiments: any;
}

export const isRTUXHomepageEnabled = ({ user, abExperiments }: RTUXHomepageEnabled): boolean => {
  const isInternalTesting = isInternalTestingEnabled(abExperiments);
  if (isInternalTesting) return true;

  const isActivated = user?.isAccepted;
  // enabled for activated user and rzp org and non-partner accounts

  return (
    isActivated &&
    user.isCountryIndia &&
    user.isOrgRZP &&
    !user.isPartner() &&
    !checkIfPosSalesAgent({ user, abExperiments })?.isPosSalesAgent &&
    isExperimentEnabled(abExperiments.rtux_homepage)
  );
};

export const useIsRTUXHomepageEnabled = (): boolean => {
  const user = useStore((state) => state.session.user) as unknown as User;
  const { abExperiments } = useSplitzService();
  return isRTUXHomepageEnabled({ user, abExperiments });
};

export const isDateRangeForInsightChartsEnabled = (abExperiments: any = {}): boolean => {
  return isExperimentEnabled(abExperiments.date_range_insight_charts);
};
