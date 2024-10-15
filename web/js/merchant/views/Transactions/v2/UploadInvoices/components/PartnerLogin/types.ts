import { FORM_STEPS, ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constants';

export type Partner = typeof ONBOARDING_PARTNERS[keyof typeof ONBOARDING_PARTNERS];
export type Status = typeof ONBOARDING_STATUS[keyof typeof ONBOARDING_STATUS];
export type FormStep = typeof FORM_STEPS[keyof typeof FORM_STEPS];

export type ButtonText = {
  primaryButton: string;
  secondaryButton?: string;
};

export type OnboardingStepData = {
  image: string;
  title: string;
  description: string;
  question?: string;
  answer?: string;
};

export type FormikValues = {
  gstin: string;
  username: string;
  password: string;
  terms: boolean;
};

export type FormWrapperProps = {
  children: React.ReactChild;
  partner: Partner;
};

export type LoginDetailsProps = {
  partner: Partner;
};

export type OtpInputProps = {
  otp: string;
  setOtp: (value: string) => void;
  onResendOtp: () => Promise<unknown>;
};

export type PartnerLoginProps = {
  partner: Partner;
  status: Status;
};

export type ModalConatinerProps = PartnerLoginProps & {
  showNotification: (data: { type: string; message: string }) => void;
};

export type SetupSuccessProps = PartnerLoginProps;
