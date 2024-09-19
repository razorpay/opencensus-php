import { useQuery } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';

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
}: {
  amount: number;
  currency: 'INR';
  enabled?: boolean;
}) => {
  const payload = {
    amount,
    currency,
  };

  return useQuery({
    queryKey: getQueryKey(payload.amount),
    queryFn: (): Promise<PricingBreakup> =>
      fetch({ url: 'settlement/ondemand/fees/dashboard', params: payload }),
    staleTime: 3000,
    retry: 1,
    enabled,
  });
};
