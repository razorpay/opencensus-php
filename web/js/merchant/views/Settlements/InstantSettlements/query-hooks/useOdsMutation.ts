import { useMutation, useQueryClient } from '@tanstack/react-query';

import {
  fetchRouteOndemandSettlements,
  fetchInstantSettlements,
} from 'merchant/reducers/collection';
import { fetchCurrentBalance, fetchOndemandRestrictions } from 'merchant/reducers/home';
import store from 'merchant/store';
import { merchantFetch } from 'merchant/utils/ajax';
import { QUERY_KEY as LINKED_ACC_QUERY_KEY } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useLinkedAccountBalance';
import { QUERY_KEY as ODS_CONFIG_QUERY_KEY } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';
import { QUERY_KEY as ODS_RES_QUERY_KEY } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSRestrictedConfig';
import { QUERY_KEY as PG_BAL_QUERY_KEY } from 'merchant/views/Settlements/InstantSettlements/query-hooks/usePGBalance';
import {
  SETTLEMENT_TYPES,
  type SettlementTransactionType,
} from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers';
import { settleLinkedAccountsBalance } from 'merchant/views/Settlements/Settlements/components/api';
import { showNotification } from 'merchant_common/reducers/notifications';

type Payload =
  | {
      type: typeof SETTLEMENT_TYPES.ROUTE;
      merchantId: string;
      amount: number;
    }
  | {
      type: typeof SETTLEMENT_TYPES.ODS;
      amount: number;
      currency: 'INR';
      settlement_payout_type?: SettlementTransactionType;
    };

export const useOdsMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (
      payload: Payload,
    ): Promise<{ type: typeof SETTLEMENT_TYPES.ROUTE } | { type: typeof SETTLEMENT_TYPES.ODS }> => {
      const isRouteOds = payload.type === SETTLEMENT_TYPES.ROUTE;
      return isRouteOds
        ? settleLinkedAccountsBalance(payload.merchantId, payload.amount)
        : merchantFetch({
            url: 'settlement/ondemand/dashboard',
            method: 'POST',
            data: {
              amount: payload.amount,
              currency: payload.currency,
              settlement_payout_type: payload?.settlement_payout_type,
            },
          });
    },
    onSuccess: (_data, payload) => {
      const isRouteOds = payload.type === SETTLEMENT_TYPES.ROUTE;
      queryClient.removeQueries({ queryKey: ODS_CONFIG_QUERY_KEY, exact: true });
      queryClient.removeQueries({ queryKey: PG_BAL_QUERY_KEY, exact: true });
      queryClient.removeQueries({ queryKey: ODS_RES_QUERY_KEY, exact: true });
      /**
       * we create custom instance of redux store while running tests, so this store
       * and test instance will be different which might causes issues in test cases.
       *
       * Not an issue here, as we used redux actions for backward compactibilty only.
       * */
      const dispatch = store.dispatch;
      dispatch(fetchCurrentBalance());
      dispatch(fetchOndemandRestrictions());
      if (isRouteOds) {
        dispatch(fetchRouteOndemandSettlements({ count: 25, skip: 0 }));
        queryClient.removeQueries({ queryKey: LINKED_ACC_QUERY_KEY, exact: true });
        return;
      }
      dispatch(fetchInstantSettlements({ count: '25', skip: '0' }));
    },
    onError: (error: any, payload) => {
      const isRouteOds = payload.type === SETTLEMENT_TYPES.ROUTE;

      const apiErrorMsg = error?.errors?.[0];
      showNotification({
        type: 'error',
        message:
          isRouteOds || !apiErrorMsg ? 'Something went wrong, Please try again!' : apiErrorMsg,
      });
    },
  });
};
