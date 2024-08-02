import React from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

export type ODSConfig = {
  blocked: boolean;
  /** True incase global/mid level limit breached */
  disable: boolean;
  max_limit?: number | null;
  available_limit?: number | null;
};

/** TODO: After react-query v5 migration, use queryoptions function from react-query */
const QUERY_KEY = ['ods-config'];

export const useODSConfig = () => {
  return useQuery({
    queryKey: QUERY_KEY,
    queryFn: (): Promise<ODSConfig> => fetch({ url: 'settlements/ondemand/merchant/config' }),
    refetchOnWindowFocus: false,
    staleTime: 10000,
    retry: false,
  });
};

type InjectedProps = {
  odsQuery: ReturnType<typeof useODSConfig>;
  invalidateOdsQuery: () => Promise<void>;
};

export const withODSConfig = <T extends Record<string, unknown>>(
  Component: React.ComponentType<T & InjectedProps>,
) => {
  return (props: T) => {
    const odsQuery = useODSConfig();
    const queryClient = useQueryClient();

    return (
      <Component
        {...props}
        odsQuery={odsQuery}
        invalidateOdsQuery={() => queryClient.invalidateQueries({ queryKey: QUERY_KEY })}
      />
    );
  };
};
