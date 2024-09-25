import { useContext, createContext } from 'react';
import noop from 'lodash/noop';

import { CONTEXT_INITIAL_STATE } from './constants';

import type { CheckoutFeatureContext } from './types';

export const checkoutFeatureContext = createContext<
  CheckoutFeatureContext<typeof CONTEXT_INITIAL_STATE.values>
>({
  ...CONTEXT_INITIAL_STATE,
  handleSave: noop,
  handleLocaleChange: noop,
  handleDiscardAllChanges: noop,
  handleCustomMessageToggle: noop,
  handleCustomMessageTextChange: noop,
  handleCustomMessageTextColorChange: noop,
  handleCustomMessageBackgroundColorChange: noop,
  handlePreviewChange: noop,
  handleFlashCheckoutToggle: noop,
  handleEmailValueChange: noop,
  handleEmailToggle: noop,
  handleMandatorySummaryPageToggle: noop,
  handleShowFinalPriceToggle: noop,
});

export const useCheckoutFeatures = () => {
  return useContext(checkoutFeatureContext);
};
