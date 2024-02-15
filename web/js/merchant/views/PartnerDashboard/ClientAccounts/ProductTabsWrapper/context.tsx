import { createContext } from 'react';

export type ProductActionsContextType = {
  handleAddMerchant: () => void;
  handleShareReferralLink: () => void;
};

const initialState = {
  handleAddMerchant: () => {},
  handleShareReferralLink: () => {},
};
export const ProductActionsContext = createContext<ProductActionsContextType>(initialState);
