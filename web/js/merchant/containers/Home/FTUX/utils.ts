import { useStore } from '@federated/apps/shell/commonStore';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';

interface FTUXHomepageEnabled {
  user: User;
  abExperiments: any;
}

export const isEligibleForFtuxV2 = ({ user, abExperiments }: FTUXHomepageEnabled): boolean => {
  const isFtuxV2Enabled = isExperimentEnabled(abExperiments?.ftuxV2);

  if (!isFtuxV2Enabled) {
    return false;
  }
  const { physical_store } = user?.merchant_business_detail?.website_details ?? {};

  const isPOSMerchant = isExperimentEnabled(abExperiments?.omniChannelGtm) && !!physical_store;

  const isEnabled = Boolean(
    user.isOrgRZP &&
      user.isCountryIndia &&
      !user.isSubMerchant &&
      !user?.isPartner?.() &&
      !isPOSMerchant &&
      !user.isTransacted,
  );

  //TODO: @mohitagrawal1305 if isTransacted is true then also check 5 settlements

  return isEnabled;
};

export const useIsFtuxV2Enabled = (): boolean => {
  const user = useStore((state) => state.session.user) as unknown as User;
  const { abExperiments } = useSplitzService();
  return isEligibleForFtuxV2({ user, abExperiments });
};
