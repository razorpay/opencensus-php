import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/createContext';

import { SHOW_FINAL_PRICE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

const ShowFinalPrice = () => {
  const { values, handleShowFinalPriceToggle } = useCheckoutEditor();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_EDITOR_FIELDS.SHOW_FINAL_PRICE]}
      feature={CHECKOUT_EDITOR_FIELDS.SHOW_FINAL_PRICE}
      title={SHOW_FINAL_PRICE_DEFAULT_VALUE.title}
      subTitle={SHOW_FINAL_PRICE_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleShowFinalPriceToggle(isChecked)}
    />
  );
};

export default ShowFinalPrice;
