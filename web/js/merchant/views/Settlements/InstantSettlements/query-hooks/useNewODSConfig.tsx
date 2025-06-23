import { useQuery } from '@tanstack/react-query';

import { fetch } from '@federated/apps/shell/rest-fetch';

export type NewODSConfig = {
  merchant_id?: string;
  blocked?: boolean;
  limit_breached?: boolean;
  available_limit?: number;
  max_limit_per_working_day?: number;
  smart_settlement_config?: {
    enabled: boolean;
  };
  restricted_config?: {
    remaining_settlement_amount: number | null;
    daily_max_amount_limit: number | null;
    remaining_attempts: number | null;
    daily_settlement_count_limit: number | null;
  };
};

/** TODO: After react-query v5 migration, use queryoptions function from react-query */
export const QUERY_KEY = ['new-ods-config'];

export const useNewODSConfig = ({ enabled }: { enabled?: boolean } | undefined = {}) => {
  return useQuery({
    queryKey: QUERY_KEY,
    queryFn: (): Promise<NewODSConfig> =>
      fetch({ url: 'capital_es/service/instant_settlements/ondemand/config' }),
    refetchOnWindowFocus: false,
    staleTime: 10000,
    retry: 1,
    enabled,
  });
};
