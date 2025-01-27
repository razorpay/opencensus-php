import { MerchantCheckoutPaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

export function getHandleSelectedConfigChange(setter: (key: string, value: any) => void) {
  return function handleSelectedConfigChange(config: MerchantCheckoutPaymentConfig) {
    setter(CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG, config);
  };
}
