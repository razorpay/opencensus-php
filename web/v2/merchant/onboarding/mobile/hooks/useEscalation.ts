import { useQuery } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';

export default function useEscalations(): any {
  const { status, data } = useQuery(
    `escalations`,
    async () => {
      const fetchEscalation = await fetch({
        url: 'merchants/onboarding/escalations',
        mode: 'live',
      });
      return fetchEscalation;
    },
    {
      retry: false,
      refetchOnWindowFocus: false,
      staleTime: Infinity,
    },
  );
  return { status, data };
}
