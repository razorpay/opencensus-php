import { User } from 'common/typings';

const EASY_ONBOARDING = 'easy_onboarding';
const ASSISTED_ONBOARDING = 'assisted_onboarding';

export const checkIfSignUpViaEasyOnboarding = (user: User | null): boolean => {
  return (
    user?.user?.signup_campaign === EASY_ONBOARDING ||
    user?.user?.signup_campaign === ASSISTED_ONBOARDING
  );
};
