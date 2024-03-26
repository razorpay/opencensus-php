import { QueryKey, useQuery } from '@tanstack/react-query';
import { AxiosRequestConfig } from 'axios';
import { fetchUCS } from 'common/services/rest/rest-fetch';

// Todo : Add type for return value
export const useUCSLayoutQuery = (
  widgetName: QueryKey,
  requestData: AxiosRequestConfig['data'] = {},
): any =>
  useQuery<any>({
    queryKey: widgetName,
    queryFn: async () => {
      const response = fetchUCS({
        method: 'POST',
        url: `rzp.dashboard.component.v1.ComponentService/GetComponent`,
        data: requestData,
      });
      return response;
    },
    refetchOnWindowFocus: false,
    staleTime: 60000 * 1, // 1 minute (keep in sync with useUCSDataQuery for consistent skeleton)
    retry: false,
  });
