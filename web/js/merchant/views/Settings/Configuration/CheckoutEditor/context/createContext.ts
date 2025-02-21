import { useContext, createContext } from 'react';
import noop from 'lodash/noop';

import { CONTEXT_INITIAL_STATE } from './constants';

import type { CheckoutEditorContext } from './types';

export const checkoutEditorContext = createContext<
  CheckoutEditorContext<typeof CONTEXT_INITIAL_STATE.values>
>({
  ...CONTEXT_INITIAL_STATE,
  handleSave: noop,
  handleSuggestionSubmit: noop,
  handleFeedbackSubmit: noop,
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
  handleLogoChange: noop,
  handleWordmarkChange: noop,
  handleRectLogoChange: noop,
  handleBrandColorChange: noop,
  handleButtonStyleChange: noop,
  handleFontStyleChange: noop,
  handleSidebarGraphicToggle: noop,
  handleSidebarGraphicValueChange: noop,
  handleTitleStyleChange: noop,
  handleBrandNameChange: noop,
  handleSaveTitleModal: noop,
  handleRtbEnable: noop,
  handleFestivalThemeToggle: noop,
  handleSelectedConfigChange: noop,
  handleConfigNameChange: noop,
  handleSetConfigAsDefault: noop,
  handleOriginalPaymentConfigChange: noop,
  handlePreviewScreenChange: noop,
  handlePaymentConfigScreenChange: noop,
  handleSelectedPaymentOptionChange: noop,
  handleCurrentExpandedCustomBlockChange: noop,
});

export const useCheckoutEditor = () => {
  return useContext(checkoutEditorContext);
};
