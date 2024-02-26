import { useContext, useEffect, useRef } from 'react';

import {
  ProductActionsContext,
  ProductActionsContextType,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/context';

type useProductActionsValue = Pick<
  ProductActionsContextType,
  'handleAddMerchant' | 'handleShareReferralLink'
> & { shouldRefetchTrigger: boolean };
const useProductActions = (): useProductActionsValue => {
  const { handleAddMerchant, handleShareReferralLink, isInviteMerchantModalOpen } =
    useContext(ProductActionsContext);

  // create a new reference
  const modalStateRef = useRef({ shouldRefetchTrigger: false, isInviteMerchantModalOpen });
  // store current value in modalStateRef

  useEffect(() => {
    // Refetch if the invite modal was just closed
    const { shouldRefetchTrigger, isInviteMerchantModalOpen: isInviteMerchantModalOpenPrev } =
      modalStateRef.current;
    const shouldRefetchNext =
      isInviteMerchantModalOpen === true &&
      isInviteMerchantModalOpenPrev !== isInviteMerchantModalOpen;
    modalStateRef.current = {
      isInviteMerchantModalOpen,
      shouldRefetchTrigger: shouldRefetchNext ? !shouldRefetchTrigger : shouldRefetchTrigger,
    };
  }, [isInviteMerchantModalOpen]); // only re-run if value changes

  return {
    handleAddMerchant,
    handleShareReferralLink,
    shouldRefetchTrigger: modalStateRef.current.shouldRefetchTrigger,
  };
};
export default useProductActions;
