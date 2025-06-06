import { useQuery } from '@tanstack/react-query';

import { fetch } from '@federated/apps/shell/rest-fetch';
import { ODS_PRICING_ENDPOINT } from 'merchant/views/Settlements/InstantSettlements/utils/common';

export type PricingBreakup = {
  items: [
    {
      name: 'settlement_ondemand';
      amount: number;
      pricing_rule: {
        percent_rate: number;
      };
    },
    {
      name: 'tax';
      amount: number;
      percentage: number;
    },
  ];
};

/** TODO: After react-query v5 migration, use queryoptions function from react-query */
const getQueryKey = (amount: number) => ['ods-pricing', { amount }];

export const usePricingBreakup = ({
  amount,
  currency,
  enabled,
  isOdsExpEnabled,
}: {
  amount: number;
  currency: 'INR';
  enabled?: boolean;
  isOdsExpEnabled?: boolean;
}) => {
  const payload = {
    amount,
    currency,
  };

  const url = isOdsExpEnabled ? ODS_PRICING_ENDPOINT.NEW : ODS_PRICING_ENDPOINT.OLD;
  return useQuery({
    queryKey: getQueryKey(payload.amount),
    queryFn: (): Promise<PricingBreakup> => fetch({ url, params: payload }),
    staleTime: 3000,
    retry: 1,
    enabled,
  });
};
