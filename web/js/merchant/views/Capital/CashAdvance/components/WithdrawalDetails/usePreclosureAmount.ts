import { useQuery } from '@tanstack/react-query';
import Withdrawals from 'merchant/models/Capital/Withdrawals';

export default function usePreclosureAmount(withdrawalId: string): any {
  const withdrawalsInstance = new Withdrawals();

  return useQuery({
    queryKey: ['get-preclosure-amount', withdrawalId],
    queryFn: () =>
      withdrawalsInstance.getPreclosureAmount({
        withdrawal_id: withdrawalId,
        product_type: 'LOC',
      }),
    cacheTime: 0,
    staleTime: 0,
    retry: 0,
    refetchOnWindowFocus: false,
  });
}
