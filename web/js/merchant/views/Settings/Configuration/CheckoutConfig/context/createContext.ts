import { useContext, createContext } from 'react';
import noop from 'lodash/noop';

import { CONTEXT_INITIAL_STATE } from './constants';

import type { CheckoutConfigContext } from './types';

export const checkoutConfigContext = createContext<
  CheckoutConfigContext<typeof CONTEXT_INITIAL_STATE.values>
>({
  ...CONTEXT_INITIAL_STATE,

  handleSave: noop,
  handleLogoChange: noop,
  handleEmailChange: noop,
  handleLocaleChange: noop,
  handleRectLogoChange: noop,
  handleBrandColorChange: noop,
  handleDiscardAllChanges: noop,
  handleCustomMessageToggle: noop,
  handleConfirmEmailRequired: noop,
  handleCustomMessageTextChange: noop,
  handleCloseEmailRequiredModal: noop,
  handleCustomMessageTextColorChange: noop,
  handleCustomMessageBackgroundColorChange: noop,
});

export const useCheckoutConfig = () => {
  return useContext(checkoutConfigContext);
};
