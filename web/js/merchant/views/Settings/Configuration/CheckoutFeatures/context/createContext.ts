import { useContext, createContext } from 'react';
import noop from 'lodash/noop';

import { CONTEXT_INITIAL_STATE } from './constants';

import type { CheckoutFeatureContext } from './types';

export const checkoutFeatureContext = createContext<
  CheckoutFeatureContext<typeof CONTEXT_INITIAL_STATE.values>
>({
  ...CONTEXT_INITIAL_STATE,

  handleSave: noop,
  handleEmailChange: noop,
  handleLocaleChange: noop,
  handleDiscardAllChanges: noop,
  handleCustomMessageToggle: noop,
  handleConfirmEmailRequired: noop,
  handleCustomMessageTextChange: noop,
  handleCloseEmailRequiredModal: noop,
  handleCustomMessageTextColorChange: noop,
  handleCustomMessageBackgroundColorChange: noop,
  handlePreviewChange: noop,
  handleFlashCheckoutToggle: noop,
});

export const useCheckoutFeatures = () => {
  return useContext(checkoutFeatureContext);
};
