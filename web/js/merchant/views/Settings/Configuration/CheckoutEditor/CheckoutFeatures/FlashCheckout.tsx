import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/createContext';

import { FLASH_CHECKOUT_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

const FlashCheckout = ({ blockData }: any) => {
  const { values, handleFlashCheckoutToggle } = useCheckoutEditor();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT]}
      feature={CHECKOUT_EDITOR_FIELDS.FLASH_CHECKOUT}
      title={FLASH_CHECKOUT_DEFAULT_VALUE.title}
      subTitle={FLASH_CHECKOUT_DEFAULT_VALUE.subTitle}
      blockData={blockData}
      toggleHandler={(isChecked) => handleFlashCheckoutToggle(isChecked)}
    />
  );
};

export default FlashCheckout;
