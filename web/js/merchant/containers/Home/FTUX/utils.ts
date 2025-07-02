import { useStore } from '@federated/apps/shell/commonStore';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';

interface FTUXHomepageEnabled {
  user: User;
  abExperiments: any;
  mode?: 'test' | 'live';
}

export const isEligibleForFtuxTransactionTimeline = ({
  user,
  abExperiments,
  mode,
}: FTUXHomepageEnabled): boolean => {
  const isFtuxV2Enabled = isExperimentEnabled(abExperiments?.ftuxV2);
  const isTransactionTimelineEnabled = isExperimentEnabled(
    abExperiments?.ftuxV2_transaction_timeline,
  );

  if (!isFtuxV2Enabled || !isTransactionTimelineEnabled) {
    return false;
  }
  const { physical_store } = user?.merchant_business_detail?.website_details ?? {};

  const isPOSMerchant = isExperimentEnabled(abExperiments?.omniChannelGtm) && !!physical_store;

  const isEnabled = Boolean(
    user.isOrgRZP &&
      user.isCountryIndia &&
      user?.user?.signup_campaign === 'easy_onboarding' &&
      !user.isSubMerchant &&
      !user?.isPartner?.() &&
      !isPOSMerchant &&
      mode !== 'test', // Timeline is only visible in live mode
  );

  return isEnabled;
};

export const isEligibleForFtuxV2 = ({ user, abExperiments }: FTUXHomepageEnabled): boolean => {
  const isFtuxV2Enabled = isExperimentEnabled(abExperiments?.ftuxV2);
  // FTUX V2 experiment must be enabled
  if (!isFtuxV2Enabled) {
    return false;
  }

  // Experiment to check if RTUX is enabled
  const isTransactionTimelineEnabled = isExperimentEnabled(
    abExperiments?.ftuxV2_transaction_timeline,
  );

  // Check if user is a POS merchant
  const { physical_store } = user?.merchant_business_detail?.website_details ?? {};
  const isPOSMerchant = isExperimentEnabled(abExperiments?.omniChannelGtm) && !!physical_store;

  // show_ftux_dashboard is a flag to determine if user still can see FTUX after transactions
  // Check if Mx hasn't transacted or passes the transaction timeline checks
  const hasAccessToFTUX =
    user.isTransacted === false || (isTransactionTimelineEnabled && !!user.show_ftux_dashboard);

  const isIssuingDashboardEnabled = user.isIssuingDashboardEnabled;
  const isIssuingGcmsEnabled = user.isIssuingGcmsEnabled;
  const isWalletMerchant = isIssuingDashboardEnabled || isIssuingGcmsEnabled;

  // Check if user is eligible for FTUX V2
  return Boolean(
    user.isOrgRZP &&
      user.isCountryIndia &&
      user?.user?.signup_campaign === 'easy_onboarding' &&
      !user.isSubMerchant &&
      !user?.isPartner?.() &&
      !isPOSMerchant &&
      !isWalletMerchant &&
      hasAccessToFTUX,
  );
};

export const useIsFtuxV2Enabled = (): boolean => {
  const user = useStore((state) => state.session.user) as unknown as User;
  const { abExperiments } = useSplitzService();
  return isEligibleForFtuxV2({ user, abExperiments });
};
