import { useQuery } from '@tanstack/react-query';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { useSnackbar, SnackContextTypes } from 'common/components/SnackBar/SnackbarContext';

interface BusinessType {
  label: string;
  id: number;
  status: string;
}

export default function useBusinessTypes(): any {
  const snackbar: SnackContextTypes = useSnackbar();
  const { status, data } = useQuery({
    queryKey: [`businessTypes`],
    queryFn: async () => {
      const businessTypes: {
        registered: BusinessType[];
        unregistered: BusinessType[];
      } = await fetch({
        url: `merchant/onboarding/business_types`,
      });

      const combineBusinessTypes: BusinessType[] = businessTypes?.registered;

      combineBusinessTypes.push(...businessTypes?.unregistered);
      return combineBusinessTypes;
    },
    refetchOnWindowFocus: false,
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });
  return { status, data };
}
