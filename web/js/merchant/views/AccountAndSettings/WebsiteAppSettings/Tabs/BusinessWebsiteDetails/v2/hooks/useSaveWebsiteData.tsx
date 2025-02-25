import { useMutation, useQueryClient } from '@tanstack/react-query';

import { fetch } from '@federated/apps/shell/rest-fetch';

import { WebsiteUpdateApiData, WebsiteUpdateApiPayload } from '../types';
import { WEBSITE_UPDATE_API_BASE_URL } from '../utils';

const saveWebsiteUpdate = async (data): Promise<WebsiteUpdateApiData> => {
  try {
    return await fetch<WebsiteUpdateApiData>({
      method: 'POST',
      url: `${WEBSITE_UPDATE_API_BASE_URL}/UpdateMerchantWebsite`,
      data,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

const useSaveWebsiteUpdate = () => {
  const queryClient = useQueryClient();

  return useMutation<WebsiteUpdateApiData, unknown, WebsiteUpdateApiPayload>({
    mutationFn: (payload) => saveWebsiteUpdate(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ['getWebsiteUpdate'],
      });
    },
    onError: () => {
      queryClient.invalidateQueries({
        queryKey: ['getWebsiteUpdate'],
      });
    },
  });
};

export default useSaveWebsiteUpdate;
