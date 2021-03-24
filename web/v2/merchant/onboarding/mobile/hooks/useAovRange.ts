import { useQuery } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import { useSnackbar } from 'v2/components/SnackBar/SnackbarContext';

export default function useAovRange(): any {
  const snackbar = useSnackbar();
  const { status, data } = useQuery(
    `aovRange`,
    async () => {
      const aovRange = await fetch({
        url: `merchant/aov-config`,
      });
      return aovRange;
    },
    {
      staleTime: Infinity,
      onError: (err: any) => snackbar.error(err.response.errors[0]),
    },
  );
  return [status, data];
}
