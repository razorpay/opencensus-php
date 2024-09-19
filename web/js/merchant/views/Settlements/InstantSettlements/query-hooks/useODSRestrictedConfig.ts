import { useQuery } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

export type ODSRestrictedConfig = {
  settlable_amount: number;
  attempts_left: number;
  max_amount_limit: number;
  settlements_count_limit: number;
};

/** TODO: After react-query v5 migration, use queryoptions function from react-query */
export const QUERY_KEY = ['ods-restricted-config'];

export const useODSRestrictedConfig = ({ enabled }: { enabled?: boolean } | undefined = {}) => {
  return useQuery({
    queryKey: QUERY_KEY,
    queryFn: (): Promise<ODSRestrictedConfig> =>
      fetch({ url: 'settlements/ondemand/feature/validate' }),
    staleTime: 10000,
    retry: 1,
    enabled,
  });
};
