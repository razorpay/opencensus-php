import { useQuery } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

import { WebsiteUpdateApiData } from '../types';
import { WEBSITE_UPDATE_API_BASE_URL } from '../utils';

const getWebsiteUpdate = async (mode): Promise<WebsiteUpdateApiData> => {
  try {
    return await fetch<WebsiteUpdateApiData>({
      method: 'POST',
      url: `${WEBSITE_UPDATE_API_BASE_URL}/GetMerchantWebsiteVerificationStatus`,
      data: {
        mode,
      },
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const useGetWebsiteUpdate = (mode) =>
  useQuery<WebsiteUpdateApiData>(['getWebsiteUpdate'], () => getWebsiteUpdate(mode), {
    refetchOnWindowFocus: false,
    refetchOnMount: true,
    retry: 0,
  });
