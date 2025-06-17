import { RazorpayUser as User } from '@libs/shared-types';
import { ExperimentInfoType } from 'common/splitz/types';
import { isExperimentEnabled } from '@libs/shared-utils';

export const isPosTabVisible = (user: User, abExperiments: ExperimentInfoType) => {
  if (isExperimentEnabled(abExperiments?.pos_api_merchant_enablement)) {
    return (
      user?.activation_status === 'activated' ||
      user?.activation_status === 'activated_mcc_pending' ||
      user?.activation_status === 'activated_kyc_pending'
    );
  }
  return false;
};

export const isPosExperimentEnabled = ({
  user,
  abExperiments,
}: {
  user: User;
  abExperiments: ExperimentInfoType;
}): boolean => {
  const businessType = Number(user?.business_type);
  const isUnregisteredMerchant =
    !isNaN(businessType) && (businessType === 11 || businessType === 2);
  const allowedFlows = ['whitelist', 'greylist'];

  const isWhitelistedForPos =
    user?.pos_activation_status !== null && typeof user?.pos_activation_status !== 'undefined'
      ? allowedFlows.includes(user?.pos_activation_flow ?? '')
      : true;

  const isBlockedForSignUpCampaign =
    user.user?.signup_campaign === 'assisted_onboarding' ||
    user.user?.signup_campaign === 'phantom_onboarding';

  const checks =
    isWhitelistedForPos &&
    !isUnregisteredMerchant &&
    !isBlockedForSignUpCampaign &&
    user.isCountryIndia &&
    user.isOrgRZP;

  if (user.is_pgos_merchant) {
    return !!checks;
  } else {
    // For non-pgos(API) merchants, we need to check the experiment, activation_status& the checks
    return isPosTabVisible(user, abExperiments) && checks;
  }
};
