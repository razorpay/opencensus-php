import { useQuery } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';

export default function useBusinessCategory(query: string): any {
  const { status, data } = useQuery(
    `businessCategories_${query}`,
    async () => {
      const businessCategoreis = await fetch({
        url: `/merchant/activation/business_details?search_string=${query}`,
      });
      return businessCategoreis;
    },
    {
      staleTime: 1000 * 60 * 5,
    },
  );
  return [status, data];
}
