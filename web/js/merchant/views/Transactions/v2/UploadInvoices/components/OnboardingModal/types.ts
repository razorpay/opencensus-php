import { ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constants';

export type Partner = typeof ONBOARDING_PARTNERS[keyof typeof ONBOARDING_PARTNERS];
export type Status = typeof ONBOARDING_STATUS[keyof typeof ONBOARDING_STATUS];

export type OnboardingModalProps = {
  partner: Partner;
  status: Status;
};

export type StepperProps = {
  data: Array<{
    description: string;
  }>;
  step: number;
};
