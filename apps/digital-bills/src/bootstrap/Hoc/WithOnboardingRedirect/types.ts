import { ONBOARDING_STATUS } from '@apps/digital-bills/src/views/Onboarding/constants';

type StatusOptions = typeof ONBOARDING_STATUS;
export type OnboardingStatusType = StatusOptions[keyof StatusOptions];

export type OnboardingStatusResponse = {
  merchantOnboardingStatus: OnboardingStatusType;
};
