import { User } from 'common/typings';

export const EASY_ONBOARDING = 'easy_onboarding';
export const ASSISTED_ONBOARDING = 'assisted_onboarding';
export const PARTNER_ASSISTED_ONBOARDING = 'partner_assisted_onboarding';

export const checkIfSignUpViaEasyOnboarding = (user: User | null): boolean => {
  return (
    user?.user?.signup_campaign === EASY_ONBOARDING ||
    user?.user?.signup_campaign === ASSISTED_ONBOARDING ||
    user?.user?.signup_campaign === PARTNER_ASSISTED_ONBOARDING
  );
};
