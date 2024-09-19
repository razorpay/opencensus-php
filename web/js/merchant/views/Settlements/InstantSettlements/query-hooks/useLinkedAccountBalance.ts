import { useQuery } from '@tanstack/react-query';

import { getLinkedAccountsBalance } from 'merchant/views/Settlements/Settlements/components/api';

export type LinkedAccount = {
  data: {
    balance: string;
  };
};

/** TODO: After react-query v5 migration, use queryoptions function from react-query */
export const QUERY_KEY = ['ods-route-balance'];

export const useLinkedAccountBalance = ({ enabled }: { enabled?: boolean }) => {
  return useQuery({
    queryKey: QUERY_KEY,
    queryFn: (): Promise<LinkedAccount> => getLinkedAccountsBalance(),
    select(data) {
      return data.data;
    },
    staleTime: 3000,
    retry: 1,
    enabled,
  });
};
