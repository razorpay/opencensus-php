import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';
import ExtraItems from 'merchant/views/Settings/Configuration/CheckoutFeatures/CustomMessageSettings/ExtraItems';

import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/index';

import { CHECKOUT_FEATURE_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/constants';
import { CUSTOM_MESSAGE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutFeatures/constants/DefaultValue';

const CustomMessageSettings = () => {
  const { values, handleCustomMessageToggle } = useCheckoutFeatures();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE].isEnabled}
      feature={CHECKOUT_FEATURE_FIELDS.CUSTOM_MESSAGE}
      title={CUSTOM_MESSAGE_DEFAULT_VALUE.title}
      subTitle={CUSTOM_MESSAGE_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleCustomMessageToggle(isChecked)}
      extraItems={<ExtraItems />}
    />
  );
};

export default CustomMessageSettings;
