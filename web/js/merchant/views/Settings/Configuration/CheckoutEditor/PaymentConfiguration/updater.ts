import {
  MerchantCheckoutPaymentConfig,
  SelectedPaymentOption,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { PAYMENT_CONFIG_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

export function getHandleSelectedConfigChange(setter: (key: string, value: any) => void) {
  return function handleSelectedConfigChange(config: MerchantCheckoutPaymentConfig) {
    setter(CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG, config);
  };
}

export function getHandleConfigNameChange(setter: (key: string, value: any) => void) {
  return function handleConfigNameChange(config: MerchantCheckoutPaymentConfig, name: string) {
    setter(CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG, { ...config, name });
  };
}

export function getHandleSetConfigAsDefault(setter: (key: string, value: any) => void) {
  return function handleSetConfigAsDefault(config: MerchantCheckoutPaymentConfig) {
    setter(CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG, { ...config, is_default: true });
  };
}

export function getHandleOriginalPaymentConfigChange(setter: (key: string, value: any) => void) {
  return function handleOriginalPaymentConfigChange(config: MerchantCheckoutPaymentConfig) {
    setter('merchantCheckoutSelectedPaymentConfig', config);
  };
}

export function getHandlePaymentConfigScreenChange(setter: (key: string, value: any) => void) {
  return function handlePaymentConfigScreenChange(screen: PAYMENT_CONFIG_SCREEN) {
    setter(CHECKOUT_EDITOR_FIELDS.PAYMENT_CONFIG_SCREEN, screen);
  };
}

export function getHandleSelectedPaymentOptionChange(setter: (key: string, value: any) => void) {
  return function handleSelectedPaymentOptionChange(paymentOption: SelectedPaymentOption) {
    setter(CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_OPTION, paymentOption);
  };
}

export function getHandleCurrentExpandedCustomBlockChange(
  setter: (key: string, value: any) => void,
) {
  return function handleCurrentExpandedCustomBlockChange(blockKey: string) {
    setter(CHECKOUT_EDITOR_FIELDS.CURRENT_EXPANDED_CUSTOM_BLOCK, blockKey);
  };
}
