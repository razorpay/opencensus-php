import { useMemo, useEffect } from 'react';
import { 
  getInsightsL1ProductsFromData,
} from 'merchant/components/SidebarV2/Products/Insights/Insights';
import { FALLBACK_INSIGHTS_DATA } from 'merchant/views/Insights/constants';
import { 
  fetchInsightsData,
  getInsightsDataWithFlags
} from 'merchant/views/Insights/utils/insightsDataManager';
import { useQuery } from '@tanstack/react-query';

const useInsightsProducts = () => {
  const { data: actualData , isLoading, isError } = useQuery({
    queryKey: ['insights_dashboard_data'],
    queryFn: fetchInsightsData,
    staleTime: 15 * 60 * 1000,
    cacheTime: 24 * 60 * 60 * 1000,
    retry: 3,
    retryOnMount: false, 
    refetchOnWindowFocus: false, 
  });
  const insightsData = isError || isLoading ? FALLBACK_INSIGHTS_DATA : actualData;
  const {
    hasMagicX,
    hasCheckoutData,
    hasSuccessRateData,
    hasApiData,
  } = getInsightsDataWithFlags();

  const productsData = useMemo(() => {
    return getInsightsL1ProductsFromData(insightsData);
  }, [insightsData]);

  return {
    productsData,
    hasMagicX,
    hasCheckoutData,
    hasSuccessRateData,
    hasApiData
  };
};

export default useInsightsProducts; 