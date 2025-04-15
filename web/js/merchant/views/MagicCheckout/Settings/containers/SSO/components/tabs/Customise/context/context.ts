import { createContext, useContext } from 'react';
import noop from 'lodash/noop';
import { SSOPreviewContextType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';

export const SSOPreviewContext = createContext<SSOPreviewContextType>({
  isDesktopPreview: false,
  setIsDesktopPreview: noop,
});

export const useCheckoutPreview = () => useContext(SSOPreviewContext);
