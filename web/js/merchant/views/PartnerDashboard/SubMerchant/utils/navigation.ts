import { NavigateFunction } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';
import { ShowNotificationT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

type SubmerchantPartial = { id: string; user: { id: string } };

export const checkIsEasyEnabledForSubmerchant = async (
  submerchant: SubmerchantPartial,
  showNotification: ShowNotificationT,
): Promise<boolean> => {
  try {
    const {
      success,
      data: { enabled },
    } = await merchantFetch({
      url: 'partnerships/twirp/rzp.partnerships.merchant.v1.MerchantAPI/IsEasyEnabled',
      method: 'POST',
      data: {
        merchant_id: submerchant.id.replace('acc_', ''),
        user_id: submerchant.user.id,
      },
    });
    return success && enabled;
  } catch (error) {
    showNotification({
      type: 'error',
      // eslint-disable-next-line
      // @ts-ignore
      message: error?.errors?.[0],
    });
    return false;
  }
};

export const openKYCFormUtil = async (
  isMWeb: boolean,
  navigate: NavigateFunction,
  submerchant: SubmerchantPartial,
  showNotification: ShowNotificationT,
): Promise<void> => {
  const isEasyEnabledForSubmerchant = await checkIsEasyEnabledForSubmerchant(
    submerchant,
    showNotification,
  );
  // check for splitz and redirect to easy for kyc
  if (isEasyEnabledForSubmerchant) {
    const easyOnboardingUrl = `${window.EASY_ONBOARDING_URL}/onboarding?account_id=${submerchant.id}`;
    // Note: this will maintain only one open tab.
    window.open(easyOnboardingUrl, 'submerchant_onboarding_via_easy');
  } else if (isMWeb) {
    navigate(`/partners/submerchants/onboarding/${submerchant.id}/steps`);
  } else {
    navigate(`/partners/submerchants/${submerchant.id}/activation`);
  }
};
