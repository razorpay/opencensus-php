import { useQuery } from 'react-query';
import axios from 'axios';

export default function useBusinessCategory(query: string): any {
  const { status, data } = useQuery(
    `businessCategories_${query}`,
    async () => {
      const businessCategoreis = await axios
        .get(`http://localhost:6006/activation/business_details?search_string=${query}`)
        .then((res) => res.data.data);
      return businessCategoreis;
    },
    {
      staleTime: 1000 * 60 * 5,
    },
  );
  return [status, data];
}
