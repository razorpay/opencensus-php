import { useQuery } from '@tanstack/react-query';
import { AxiosRequestConfig } from 'axios';
import { fetchUCS } from 'common/services/rest/rest-fetch';

// Todo : Add type for response
export const fetchUCSData = async (requestData: AxiosRequestConfig['data']): Promise<any> => {
  const response = fetchUCS({
    method: 'POST',
    url: `rzp.dashboard.component.v1.ComponentService/GetComponentData`,
    data: requestData,
  });
  return response;
};

export const useUCSDataQuery = (widgetName: Array<string>, data = {}): any =>
  useQuery<any>({
    queryKey: widgetName,
    queryFn: () => fetchUCSData(data),
    refetchOnWindowFocus: false,
    staleTime: 60000 * 1, // 1 minute
    retry: false,
  });
