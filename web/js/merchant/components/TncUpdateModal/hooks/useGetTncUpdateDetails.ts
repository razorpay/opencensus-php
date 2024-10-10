import { useQuery } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

import { TncUpdateApiData } from 'merchant/components/TncUpdateModal/types';

import { TNC_UPDATE_API_BASE_URL } from 'merchant/components/TncUpdateModal/constants';

const getTncUpdate = async (): Promise<TncUpdateApiData> => {
  try {
    return await fetch<TncUpdateApiData>({
      method: 'POST',
      url: `${TNC_UPDATE_API_BASE_URL}/GetMerchantTncUpdate`,
    });
  } catch (e: any) {
    throw new Error(e?.response?.errors?.[0]);
  }
};

export const useGetTncUpdate = () =>
  useQuery<TncUpdateApiData>(['tncUpdate'], () => getTncUpdate(), {
    refetchOnWindowFocus: false,
    refetchOnMount: true,
    retry: 0,
  });
