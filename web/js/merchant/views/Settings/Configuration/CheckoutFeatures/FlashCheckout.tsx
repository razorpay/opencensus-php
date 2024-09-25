import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/createContext';

import { CHECKOUT_FEATURE_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/constants';
import { FLASH_CHECKOUT_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutFeatures/constants/DefaultValue';

const FlashCheckout = () => {
  const { values, handleFlashCheckoutToggle } = useCheckoutFeatures();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT]}
      feature={CHECKOUT_FEATURE_FIELDS.FLASH_CHECKOUT}
      title={FLASH_CHECKOUT_DEFAULT_VALUE.title}
      subTitle={FLASH_CHECKOUT_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleFlashCheckoutToggle(isChecked)}
    />
  );
};

export default FlashCheckout;
