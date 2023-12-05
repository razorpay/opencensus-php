import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';

export default function useBusinessName(query: string): any {
  const snackbar = useSnackbar();
  const { status, data } = useQuery({
    queryKey: [`businessName_${query}`],
    queryFn: async () => {
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
    retry: false,
    refetchOnWindowFocus: false,
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });
  return [status, data];
}
