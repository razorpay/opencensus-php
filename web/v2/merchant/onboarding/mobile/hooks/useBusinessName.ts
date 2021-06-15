import { useQuery } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import { useSnackbar } from 'v2/components/SnackBar/SnackbarContext';

export default function useBusinessName(query: string): any {
  const snackbar = useSnackbar();
  const { status, data } = useQuery(
    `businessName_${query}`,
    async () => {
      if (!query || query.length < 3) {
        return {
          results: [],
        };
      } else {
        const businessNames = await fetch({
          url: `merchant/activation/company_search?search_string=${query}`,
        });
        return businessNames;
      }
    },
    {
      retry: false,
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      onError: (err: any) => snackbar.error(err?.response?.errors[0]),
    },
  );
  return [status, data];
}
