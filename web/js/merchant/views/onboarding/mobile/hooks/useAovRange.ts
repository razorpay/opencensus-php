import { useQuery } from '@tanstack/react-query';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';

export default function useAovRange(): any {
  const snackbar = useSnackbar();
  const { status, data } = useQuery({
    queryKey: [`aovRange`],
    queryFn: async () => {
      const aovRange = await fetch({
        url: `merchant/aov-config`,
      });
      return aovRange;
    },
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });
  return [status, data];
}
