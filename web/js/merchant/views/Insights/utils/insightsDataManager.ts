import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from '@libs/web-nexus/merchant/reducers/notifications';
import { FALLBACK_INSIGHTS_DATA } from 'merchant/views/Insights/constants';
import { queryClient } from 'merchant/ProductDashboard';

export type InsightMetric = Record<string, string>;
export type InsightsApiResponse = Record<string, InsightMetric[]>;

interface MethodAggregateResponse {
  data: InsightsApiResponse;
}

const notifyError = (message: string) => {
  showNotification({
    type: 'error',
    message: message,
  });
};

export const fetchInsightsData = async (): Promise<InsightsApiResponse> => {
  try {
    const response = await merchantFetch<MethodAggregateResponse>({
      absUrl: '/merchant/insightx/method_aggregate',
    });
    
    if (response && 
        response.data && 
        typeof response.data === 'object' && 
        Object.keys(response.data).length > 0) {
      
      const isValidFormat = Object.values(response.data).every(
        metrics => Array.isArray(metrics) && metrics.length > 0
      );
      
      if (isValidFormat) {
        const data = { ...response.data };
        
        if (data['success-rate'] && Array.isArray(data['success-rate']) && data['success-rate'].length > 0) {
          const hasOverviewMetric = data['success-rate'].some(
            metric => Object.keys(metric)[0].toLowerCase() === 'overview'
          );
          
          if (!hasOverviewMetric) {
            let totalSuccessRate = 0;
            
            data['success-rate'].forEach(metric => {
              const metricValue = Object.values(metric)[0];
              if (metricValue && !isNaN(Number(metricValue))) {
                totalSuccessRate += Number(metricValue);
              }
            });
            
            data['success-rate'].unshift({ "overview": String(totalSuccessRate) });
          }
        }
        return data;
      }
      
      notifyError('API response has invalid format');
      throw new Error('API response has invalid format');
    } else {
      notifyError('API returned empty or invalid data structure');
      throw new Error('API returned empty or invalid data structure');
    }
  } catch (error) {
    notifyError('Error fetching insights data');
    throw new Error('Error fetching insights data');
  }
};

export const prefetchInsightsData = async (): Promise<void> => {
  try {
    await queryClient.prefetchQuery({
      queryKey: ['insights_dashboard_data'],
      queryFn: fetchInsightsData,
      staleTime: 15 * 60 * 1000, 
      cacheTime: 24 * 60 * 60 * 1000, 
    });
  } catch (error) {
    console.error('Error prefetching insights data', error);
  }
};

export const getInsightsDataFromCache = (): InsightsApiResponse => {
  try {
    if (queryClient) {
      const cachedData = queryClient.getQueryData<InsightsApiResponse>(['insights_dashboard_data']);
      if (cachedData) {
        return cachedData;
      }
    }
  } catch (error) {
    showNotification({
      type: 'error',
      message: 'Error fetching insights data from cache',
    });
  }
  
  return FALLBACK_INSIGHTS_DATA;
};

export const hasApiDataInCache = (): boolean => {
  try {
    if (queryClient) {
      const cachedData = queryClient.getQueryData<InsightsApiResponse>(['insights_dashboard_data']);
      return cachedData !== undefined;
    }
  } catch (error) {
    console.error('Error checking API data in cache', error);
  }
  return false;
};

export const getInsightsDataWithFlags = () => {
  const insightsData = getInsightsDataFromCache();
  const checkoutData = insightsData['checkout'] || [];
  const successRateData = insightsData['success-rate'] || [];
  const hasApiData = hasApiDataInCache();
  
  return {
    insightsData,
    hasCheckoutData: checkoutData.length > 0,
    hasSuccessRateData: successRateData.length > 0,
    hasMagicX: checkoutData.some(
      (item) => Object.keys(item)[0].toLowerCase() === 'magicx'
    ),
    hasApiData,
  };
};
