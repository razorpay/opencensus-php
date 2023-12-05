import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';

export default function useBusinessCategory(query: string): any {
  const snackbar = useSnackbar();
  const { status, data } = useQuery({
    queryKey: [`businessCategories_${query}`],
    queryFn: async () => {
      const businessCategoreis = await fetch({
        url: `merchant/activation/business_details?search_string=${query}`,
      });
      return businessCategoreis;
    },
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });
  return [status, data];
}
