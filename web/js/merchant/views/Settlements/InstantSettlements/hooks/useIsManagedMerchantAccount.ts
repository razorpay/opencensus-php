import { useSplitzService } from 'common/splitz';

const useIsManagedMerchantAccount = (): {
  isManagedMerchantAccount: boolean;
} => {
  const splitzOffer = useSplitzService().abExperiments?.is_managed_merchant_account;
  const isManagedMerchantAccount = splitzOffer?.variables?.result === 'on';
  return {
    isManagedMerchantAccount,
  };
};

export { useIsManagedMerchantAccount };
