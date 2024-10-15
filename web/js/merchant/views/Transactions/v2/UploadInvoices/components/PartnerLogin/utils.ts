import {
  FORM_INITIAL_VALUES,
  FORM_STEPS,
  ONBOARDING_PARTNERS,
  ONBOARDING_STATUS,
  OnboardingStepMapping,
} from './constants';
import { ButtonText, FormStep, OnboardingStepData, Partner, Status } from './types';

/**
  ModalContainer.tsx utils
*/

export const getFormStep = (partner: Partner, status: Status): FormStep => {
  if (partner === ONBOARDING_PARTNERS.E_INVOICE && status === ONBOARDING_STATUS.EXPIRED) {
    return FORM_STEPS.SETUP_INFO;
  }
  return FORM_STEPS.LOGIN_DETAILS;
};

export const getButtonText = (partner: Partner, formStep: FormStep, status: Status): ButtonText => {
  if (partner === ONBOARDING_PARTNERS.GST_PORTAL && formStep === FORM_STEPS.LOGIN_DETAILS) {
    return { primaryButton: 'Generate OTP', secondaryButton: 'Back to Guide' };
  }
  if (partner === ONBOARDING_PARTNERS.E_INVOICE && formStep === FORM_STEPS.LOGIN_DETAILS) {
    return { primaryButton: 'Login', secondaryButton: 'Back to Guide' };
  }
  if (formStep === FORM_STEPS.SETUP_INFO && status === ONBOARDING_STATUS.EXPIRED) {
    return { primaryButton: 'Set up now', secondaryButton: 'Do it later' };
  }
  return { primaryButton: 'Proceed' };
};

export const isButtonDisabled = (formStep: FormStep, isValid: boolean, otp = '') => {
  if (formStep === FORM_STEPS.LOGIN_DETAILS && !isValid) {
    return true;
  }
  if (formStep === FORM_STEPS.OTP && otp.length !== 6) {
    return true;
  }
  return false;
};

export const getModalTitle = (partner: Partner) => {
  if (partner === ONBOARDING_PARTNERS.GST_PORTAL) {
    return 'Login to GST portal';
  }
  return 'Login to NIC portal';
};

/**
  FormWrapper.tsx utils
*/

export const getInitialFormValues = (partner: string, gstin = '') => {
  const formValues = FORM_INITIAL_VALUES[partner];
  return {
    ...formValues,
    gstin,
  };
};

/**
  SetupSuccess.tsx utils
*/

export const getOnboardingStepData = (partner: Partner, status: Status): OnboardingStepData => {
  if (partner === ONBOARDING_PARTNERS.E_INVOICE && status === ONBOARDING_STATUS.EXPIRED) {
    return OnboardingStepMapping.gstPortalDeboarded;
  }
  if (partner === ONBOARDING_PARTNERS.E_INVOICE) {
    return OnboardingStepMapping.nicPortalOnboarded;
  }
  return OnboardingStepMapping.gstPortalOnboarded;
};
