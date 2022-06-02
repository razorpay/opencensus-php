import { useQuery } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useSnackbar, SnackContextTypes } from 'common/components/SnackBar/SnackbarContext';

interface BusinessType {
  label: string;
  id: number;
  status: string;
}

export default function useBusinessTypes(): any {
  const snackbar: SnackContextTypes = useSnackbar();
  const { status, data } = useQuery(
    `businessTypes`,
    async () => {
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
    {
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      onError: (err: any) => {
        if (err?.response?.errors) snackbar.error(err.response.errors[0]);
      },
    },
  );
  return { status, data };
}
