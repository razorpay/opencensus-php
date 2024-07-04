import { useStore } from 'shell/commonStore';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import { User } from 'common/typings';

interface RTUXHomepageEnabled {
  user: User;
  abExperiments: any;
}

export const isRTUXHomepageEnabled = ({ user, abExperiments }: RTUXHomepageEnabled): boolean => {
  const isActivated = user?.isAccepted;
  // enabled for activated user and rzp org and non-partner accounts

  return (
    isActivated &&
    user.isINCountry &&
    (user.isOrgRZP || user.isVasTestingMerchant) &&
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
