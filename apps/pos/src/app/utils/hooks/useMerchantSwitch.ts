import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { switchMerchant } from 'apps/pos/src/app/apis/SalesAssistedOnboarding';
import { SwitchMerchantAPIResponse } from 'apps/pos/src/app/typings/SalesAssistedOnboarding';
import { APIResponse } from 'apps/pos/src/app/typings/common';

interface UseMerchantSwitchProps {
  onSuccess: (data: SwitchMerchantAPIResponse) => void;
  onError: (data: SwitchMerchantAPIResponse) => void;
}

interface UseMrchantSwitch {
  isLoading: boolean;
  handleSwitchMerchant: (merchantId: string) => void;
}

const useMerchantSwitch = ({ onSuccess, onError }: UseMerchantSwitchProps): UseMrchantSwitch => {
  const [merchantId, setMerchantId] = useState<string>('');
  const { isLoading } = useQuery<SwitchMerchantAPIResponse, APIResponse<null, string>>(
    ['merchantSwitch'],
    () => switchMerchant({ merchantId }),
    {
      enabled: !!merchantId,
      cacheTime: 0,
      onSuccess: (data) => {
        setMerchantId('');
        onSuccess?.(data);
      },
      onError: (data) => {
        setMerchantId('');
        onError?.(data);
      },
    },
  );

  return {
    isLoading,
    handleSwitchMerchant: (merchantId: string) => setMerchantId(merchantId),
  };
};

export default useMerchantSwitch;
