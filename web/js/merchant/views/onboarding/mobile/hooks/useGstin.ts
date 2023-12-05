import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useApp } from 'common/context/App';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';

export default function useGstin(): any {
  const snackbar = useSnackbar();
  const {
    experiments: { isGstinAutoPopulate },
  } = useApp();
  let gstinDetails: null | any = null;

  const { status, data }: any = useQuery({
    queryKey: [`gstinDetails`],
    queryFn: async () => {
      if (!isGstinAutoPopulate) {
        return null;
      }
      const fetchGstinDetails = await fetch({
        url: `merchant/activation/gst_details`,
      });
      return fetchGstinDetails;
    },
    retry: false,
    refetchOnWindowFocus: false,
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });
  if (data?.results && data.results.length) {
    gstinDetails = {
      gstinList: data.results,
      defaultGstin: data.results[0],
    };
  }

  return { status, gstinDetails };
}
