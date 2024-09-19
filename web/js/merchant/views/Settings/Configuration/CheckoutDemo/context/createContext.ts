import { createContext, useContext } from 'react';
import noop from 'lodash/noop';

export type CheckoutPreviewContext = {
  isDesktopPreview: boolean;
  setIsDesktopPreivew: React.Dispatch<React.SetStateAction<boolean>>;
};

export const CheckoutPreviewContext = createContext<CheckoutPreviewContext>({
  isDesktopPreview: true,
  setIsDesktopPreivew: noop,
});

export const useCheckoutPreview = () => useContext(CheckoutPreviewContext);
