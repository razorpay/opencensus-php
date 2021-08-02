import { useQuery } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import { useSnackbar } from 'v2/components/SnackBar/SnackbarContext';

export default function useBusinessCategory(query: string): any {
  const snackbar = useSnackbar();
  const { status, data } = useQuery(
    `businessCategories_${query}`,
    async () => {
      const businessCategoreis = await fetch({
        url: `merchant/activation/business_details?search_string=${query}`,
      });
      return businessCategoreis;
    },
    {
      staleTime: Infinity,
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );
  return [status, data];
}
