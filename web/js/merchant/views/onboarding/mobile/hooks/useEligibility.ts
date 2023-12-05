import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import { useApp } from 'common/context/App';

export default function useEligibility(): any {
  const snackbar = useSnackbar();
  const { experiments } = useApp();
  const isFeEasyDashboardNCEnabled = experiments.isFeEasyDashboardNCEnabled;
  const { data } = useQuery({
    queryKey: [`eligibility`],
    queryFn: async () => {
      const eligibility = await fetch({
        url: `merchant/activation/clarifications/eligibility`,
        mode: 'live',
      });
      return eligibility;
    },
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
    enabled: isFeEasyDashboardNCEnabled,
  });
  return data;
}
