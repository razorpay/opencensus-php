import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';
import ExtraItems from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/CustomMessageSettings/ExtraItems';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { CUSTOM_MESSAGE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';

const CustomMessageSettings = () => {
  const { values, handleCustomMessageToggle } = useCheckoutEditor();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE].isEnabled}
      feature={CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE}
      title={CUSTOM_MESSAGE_DEFAULT_VALUE.title}
      subTitle={CUSTOM_MESSAGE_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleCustomMessageToggle(isChecked)}
      extraItems={<ExtraItems />}
    />
  );
};

export default CustomMessageSettings;
