import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';

export default function useConfigDetails(namespace: string): any {
  const { status, data, refetch } = useQuery({
    queryKey: [`store`],
    queryFn: async () => {
      const fetchData = await fetch({
        url: `merchants/config/store?namespace=${namespace}`,
        method: 'GET',
      });
      return fetchData;
    },
    retry: false,
    staleTime: Infinity,
  });
  return { status, data, refetch };
}
