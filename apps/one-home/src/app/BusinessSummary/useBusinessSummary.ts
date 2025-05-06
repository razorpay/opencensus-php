import { useQuery, UseQueryResult } from '@tanstack/react-query';
import fetchUCSData from '../../services/ucs/fetchUcsData';
import {
  BusinessSummaryResponse,
  BusinessSummarySuccessResponse,
  BusinessSummaryErrorResponse,
} from './types';

const useBusinessSummary = (
  selectedFilter: string,
): UseQueryResult<BusinessSummarySuccessResponse, BusinessSummaryErrorResponse> => {
  return useQuery<BusinessSummarySuccessResponse, BusinessSummaryErrorResponse>(
    ['one_home_business_summary', selectedFilter],
    async () => {
      try {
        const response: BusinessSummaryResponse = await fetchUCSData({
          alias: 'home_business_summary',
          date_time: {
            quick: selectedFilter,
          },
        });

        if (!response || 'error' in response) {
          throw response as BusinessSummaryErrorResponse;
        }

        return response as BusinessSummarySuccessResponse;
      } catch (e) {
        throw e;
      }
    },
    {
      staleTime: 5 * 60 * 1000,
      cacheTime: 5 * 60 * 1000,
      refetchOnWindowFocus: false,
      retry: false,
      networkMode: 'always',
    },
  );
};

export default useBusinessSummary;
