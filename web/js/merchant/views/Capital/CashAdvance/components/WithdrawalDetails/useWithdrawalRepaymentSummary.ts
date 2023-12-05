import { useQuery } from '@tanstack/react-query';
import Withdrawals from 'merchant/models/Capital/Withdrawals';
import { STATUSES } from 'merchant/views/Capital/CashAdvance/constants';

export const useWithdrawalRepaymentSummary = (withdrawalId: string, status: string): any => {
  const withdrawalsInstance = new Withdrawals();

  return useQuery({
    queryKey: ['get-withdrawal-repayment-summary', withdrawalId],
    queryFn: () =>
      withdrawalsInstance.fetchWithdrawalRepaymentSummary({
        withdrawal_id: withdrawalId,
      }),
    cacheTime: 0,
    staleTime: 0,
    retry: 0,
    refetchOnWindowFocus: false,
    enabled: ![STATUSES.INITIATED, STATUSES.FAILED, STATUSES.REPAID].includes(status),
  });
};
