import { useQuery } from 'react-query';
import { InsightResponse, useInsightsProp } from './types';
import fetchUCSData from '../../services/ucs/fetchUcsData';

const useInsights = ({ key, aliasKey, input_data }: useInsightsProp) => {
  const fetchData = async (): Promise<InsightResponse> => {
    try {
      const response = await fetchUCSData({
        alias: aliasKey,
        date_time: {
          quick: input_data,
        },
      });

      if (!response || 'error' in response) {
        throw new Error(`No data available for alias: ${aliasKey}`);
      }

      return response as InsightResponse;
    } catch (error) {
      console.warn(`Failed to fetch data for ${aliasKey} section:`, error);
      throw new Error(
        error instanceof Error ? error.message : `Unknown error fetching insights for ${aliasKey}`,
      );
    }
  };

  const { data, isLoading, isError, error } = useQuery([key, input_data], fetchData, {
    staleTime: 5 * 60 * 1000,
    cacheTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
    retry: false,
    networkMode: 'always',
  } as unknown as any);

  return {
    insightsData: data,
    isLoading,
    isError,
    error,
  };
};

export default useInsights;
