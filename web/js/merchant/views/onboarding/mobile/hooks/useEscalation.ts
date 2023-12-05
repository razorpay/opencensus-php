import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';

export default function useEscalations(): any {
  const { status, data } = useQuery({
    queryKey: [`escalations`],
    queryFn: async () => {
      const fetchEscalation = await fetch({
        url: 'merchants/onboarding/escalations',
        mode: 'live',
      });
      return fetchEscalation;
    },
    retry: false,
    refetchOnWindowFocus: false,
    staleTime: Infinity,
  });
  return { status, data };
}
