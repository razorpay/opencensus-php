import { createPopup } from '@typeform/embed';
import {
  postOnboardingStatusApi,
  updateOnboardingStatusApi,
} from 'merchant/views/CustomerTrust/utils/api';
import {
  FetchOnboardingResponse,
  OnboardingStatus,
  SetOnboardingStatus,
} from 'merchant/views/CustomerTrust/types';

export const handleFormSubmitted = async (
  setOnboardingStatus: SetOnboardingStatus,
  show: any
) => {
  try {
    const response: FetchOnboardingResponse = await updateOnboardingStatusApi();
    if (response.success && response.data.onboarding_status === 'completed') {
      setOnboardingStatus('completed');
      show({
        type: 'informational',
        content:  'Form submitted successfully!',
        color: 'positive',
      });
    } else {
      show({
        type: 'informational',
        content: 'Failed to submit form. Please try again!',
        color: 'negative',
      });
    }
  } catch (e) {
    show({
      type: 'informational',
      content: 'Failed to submit form. Please try again!',
      color: 'negative',
    });
  }
};

const handleFormPopup = (
  setOnboardingStatus: SetOnboardingStatus,
  show: any,
) => {
  const formId = 'YpJfvpE6';
  const formPopup = createPopup(formId, {
    hideHeaders: true,
    hideFooter: true,
    onSubmit: () => handleFormSubmitted(setOnboardingStatus, show),
  });

  formPopup.toggle();
};

export const handleInterestedClick = async (
  onboardingStatus: OnboardingStatus,
  setOnboardingStatus: SetOnboardingStatus,
  show: any
) => {
  if (onboardingStatus === 'interested') {
    show({
      type: 'informational',
      content: 'You have already shown interest! Please fill the form to complete the process.',
    });
    handleFormPopup(setOnboardingStatus, show);
    return;
  }
  try {
    const response: FetchOnboardingResponse = await postOnboardingStatusApi();
    if (response.success && response.data.onboarding_status === 'interested') {
      setOnboardingStatus('interested');
      handleFormPopup(setOnboardingStatus, show);
    } else {
      show({
        type: 'informational',
        content: 'Something went wrong. Please try again!',
        color: 'negative',
      });
    }
  } catch (e) {
    show({
      type: 'informational',
      content: 'Something went wrong. Please try again!',
      color: 'negative',
    });
  }
};
