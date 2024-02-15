import { useContext } from 'react';

import {
  ProductActionsContext,
  ProductActionsContextType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/context';

const useProductActions = (): ProductActionsContextType => {
  const { handleAddMerchant, handleShareReferralLink } = useContext(ProductActionsContext);
  return { handleAddMerchant, handleShareReferralLink };
};
export default useProductActions;
