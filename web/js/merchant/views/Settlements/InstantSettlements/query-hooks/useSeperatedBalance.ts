import { useQuery } from '@tanstack/react-query';
import { fetch } from '@federated/apps/shell/rest-fetch';
import { showNotification } from 'merchant_common/reducers/notifications';
import { SEPERATEDBALANCEURL } from 'merchant/views/Settlements/InstantSettlements/utils/common';

export type SeperatedBalance = {
  derived_balance: number;
  ledger_balance: number;
  balance: number;
  on_hold: boolean;
};

export const QUERY_KEY = ['seperated-balance'];

type FetchResponse = {
  items?: Array<{
    derived_balance?: number;
    ledger_balance?: number;
    balance?: number;
    on_hold?: boolean;
  }>;
};

export const useSeperatedBalance = (isBalanceSeperationExpEnabled) => {
  return useQuery({
    queryKey: QUERY_KEY,
    enabled: isBalanceSeperationExpEnabled,
    queryFn: async (): Promise<SeperatedBalance> => {
      try {
        const response: FetchResponse = await fetch({
          url: SEPERATEDBALANCEURL,
        });

        if (!response?.items?.length) {
          showNotification({
            type: 'error',
            message: 'No Balance data received from the server.',
          });
          throw new Error('No balance data received from the server.');
        }

        const item = response.items[0];

        return {
          derived_balance: Number(item?.derived_balance ?? 0),
          ledger_balance: Number(item?.ledger_balance ?? 0),
          balance: Number(item?.balance ?? 0),
          on_hold: item?.on_hold ?? false,
        };
      } catch (error) {
        showNotification({
          type: 'error',
          message: 'Failed to fetch Balance',
        });
        return {
          derived_balance: 0,
          ledger_balance: 0,
          balance: 0,
          on_hold: false,
        };
      }
    },
    staleTime: 2000,
    retry: 1,
  });
};
