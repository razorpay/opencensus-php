import { createContext } from 'react';

export type ProductActionsContextType = {
  handleAddMerchant: () => void;
  handleShareReferralLink: () => void;
  isInviteMerchantModalOpen: boolean;
};

const initialState = {
  handleAddMerchant: () => {},
  handleShareReferralLink: () => {},
  isInviteMerchantModalOpen: false,
};
export const ProductActionsContext = createContext<ProductActionsContextType>(initialState);
