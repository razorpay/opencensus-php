import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/createContext';

import { CHECKOUT_FEATURE_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/constants';
import { SHOW_FINAL_PRICE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutFeatures/constants/DefaultValue';

const ShowFinalPrice = () => {
  const { values, handleShowFinalPriceToggle } = useCheckoutFeatures();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_FEATURE_FIELDS.SHOW_FINAL_PRICE]}
      feature={CHECKOUT_FEATURE_FIELDS.SHOW_FINAL_PRICE}
      title={SHOW_FINAL_PRICE_DEFAULT_VALUE.title}
      subTitle={SHOW_FINAL_PRICE_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleShowFinalPriceToggle(isChecked)}
    />
  );
};

export default ShowFinalPrice;
