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
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

const useInsightsProducts = () => {
  const { abExperiments } = useSplitzService();
  const isInsightsExpEnabled = abExperiments ? isExperimentEnabled(abExperiments['insights_experiment']) : false;

  const { data: actualData, isLoading, isError } = useQuery({
    queryKey: ['insights_dashboard_data'],
    queryFn: fetchInsightsData,
    staleTime: 15 * 60 * 1000,
    cacheTime: 24 * 60 * 60 * 1000,
    enabled: isInsightsExpEnabled,
    retry: 3,
    retryOnMount: false, 
    refetchOnWindowFocus: false, 
  });
  
  const insightsData = useMemo(() => {
    if (!isInsightsExpEnabled) {
      return FALLBACK_INSIGHTS_DATA;
    }
    return isError || isLoading ? FALLBACK_INSIGHTS_DATA : (actualData || FALLBACK_INSIGHTS_DATA);
  }, [isInsightsExpEnabled, isError, isLoading, actualData]);

  const {
    hasMagicX = false,
    hasCheckoutData = false,
    hasSuccessRateData = false,
    hasApiData = false,
  } = isInsightsExpEnabled ? getInsightsDataWithFlags() : {};

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