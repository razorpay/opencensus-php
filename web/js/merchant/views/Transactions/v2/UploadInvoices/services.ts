import { merchantFetch } from 'merchant/utils/ajax';

import { FetchOnboardingStatusResponse, FetchStatsResponse } from './types';

export const fetchStats = async (): Promise<FetchStatsResponse> => {
  try {
    const response = await merchantFetch(
      'payments_cross_border_live/v1/documents/document_metadata/metadata',
    );
    if (response?.data?.data) {
      return response?.data?.data;
    }
    return {}; // Implicitly typed as empty object
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};

export const fetchOnboardingStatus = async (): Promise<FetchOnboardingStatusResponse> => {
  try {
    const response = await merchantFetch('payments_cross_border_live/v1/onboard/partner/status');
    const onboardingData = response?.data?.partners;
    if (onboardingData) {
      return onboardingData;
    }
    return []; // Implicitly typed as empty object
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};
