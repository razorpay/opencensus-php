import { useMutation } from '@tanstack/react-query';

import { fetch } from '@federated/apps/shell/rest-fetch';

import {
  SaveTncUpdateAcceptancePayload,
  SaveTncUpdateAcceptanceApiData,
  TriggerAnalytics,
} from 'merchant/components/TncUpdateModal/types';

import { TNC_UPDATE_API_BASE_URL } from 'merchant/components/TncUpdateModal/constants';

const saveTncUpdateAcceptance = async (
  data: SaveTncUpdateAcceptancePayload,
): Promise<SaveTncUpdateAcceptanceApiData> => {
  try {
    return await fetch<SaveTncUpdateAcceptanceApiData>({
      method: 'POST',
      url: `${TNC_UPDATE_API_BASE_URL}/SaveMerchantTncAcceptance`,
      data,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0] || 'Something went wrong');
  }
};

export const useSaveTncUpdateAcceptance = (triggerAnalytics: TriggerAnalytics) => {
  const mutation = useMutation<
    SaveTncUpdateAcceptanceApiData,
    unknown,
    SaveTncUpdateAcceptancePayload
  >({
    mutationFn: (payload) => saveTncUpdateAcceptance(payload),
    onSuccess: () => {
      triggerAnalytics({
        api_success: true,
      });
    },
    onError: (errorData) => {
      triggerAnalytics({
        api_success: false,
        error: `${errorData}`,
      });
    },
  });

  return {
    mutate: mutation.mutateAsync,
    isLoading: mutation.isLoading,
  };
};
