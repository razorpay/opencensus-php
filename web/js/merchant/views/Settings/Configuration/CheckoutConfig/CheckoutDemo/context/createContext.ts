import { createContext, useContext } from 'react';
import noop from 'lodash/noop';

export type CheckoutPreviewContextType = {
  isDesktopPreview: boolean;
  setIsDesktopPreivew: React.Dispatch<React.SetStateAction<boolean>>;
};

export const CheckoutPreviewContext = createContext<CheckoutPreviewContextType>({
  isDesktopPreview: true,
  setIsDesktopPreivew: noop,
});

export const useCheckoutPreview = () => useContext(CheckoutPreviewContext);
