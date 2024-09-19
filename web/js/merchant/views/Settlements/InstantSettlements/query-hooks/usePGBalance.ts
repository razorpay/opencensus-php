import { useQuery } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

export type PGBalance = {
  balance: number;
};

/** TODO: After react-query v5 migration, use queryoptions function from react-query */
export const QUERY_KEY = ['pg-balance'];

export const usePGBalance = () => {
  return useQuery({
    queryKey: QUERY_KEY,
    queryFn: (): Promise<PGBalance> => fetch({ url: 'balance' }),
    staleTime: 2000,
    retry: 1,
  });
};
