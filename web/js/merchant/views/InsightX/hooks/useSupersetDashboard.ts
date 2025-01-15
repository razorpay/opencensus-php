import { useQuery } from '@tanstack/react-query';

import { merchantFetch } from 'merchant/utils/ajax';

const fetchGuestToken = async (): Promise<string> => {
  try {
    const response = await merchantFetch({
      absUrl: '/merchant/insightx/generate_superset_token',
    });
    return response?.data?.guestToken;
  } catch (error) {
    return `Error Occured: ${error}`;
  }
};

export const useSupersetDashboard = () => {
  const { data, refetch, isLoading, isError, error } = useQuery({
    queryFn: fetchGuestToken,
    queryKey: ['superset:dashboard'],
    networkMode: 'always',
    enabled: true,
  });
  return { data, refetch, isLoading, isError, error };
};
