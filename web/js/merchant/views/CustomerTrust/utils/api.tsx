import { merchantFetch } from 'merchant/utils/ajax';
import { FetchOnboardingResponse } from 'merchant/views/CustomerTrust/types';

export const fetchOnboardingStatusApi = (): Promise<FetchOnboardingResponse> => {
  return merchantFetch({
    url: `dashboard/buyer_protection/onboarding`,
    method: 'get',
  });
};

export const postOnboardingStatusApi = (): Promise<FetchOnboardingResponse> => {
  return merchantFetch({
    url: `dashboard/buyer_protection/onboarding`,
    method: 'post',
    data: {
      onboarding_status: 'interested',
    },
  });
};

export const updateOnboardingStatusApi = (): Promise<FetchOnboardingResponse> => {
  return merchantFetch({
    url: `dashboard/buyer_protection/onboarding`,
    method: 'patch',
    data: {
      onboarding_status: 'completed',
    },
  });
};
