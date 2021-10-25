import { useQuery } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';

export default function useConfigDetails(namespace: string): any {
  const { status, data, refetch } = useQuery(
    `store`,
    async () => {
      const fetchData = await fetch({
        url: `merchants/config/store?namespace=${namespace}`,
        method: 'GET',
      });
      return fetchData;
    },
    {
      retry: false,
      staleTime: Infinity,
    },
  );
  return { status, data, refetch };
}
