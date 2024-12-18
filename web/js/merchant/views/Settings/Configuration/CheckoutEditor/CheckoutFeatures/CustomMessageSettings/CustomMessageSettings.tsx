import React from 'react';

import ExtraItems from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/CustomMessageSettings/ExtraItems';
import { CUSTOM_MESSAGE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import track from './track';

const CustomMessageSettings = ({ blockData }: any) => {
  const { values, handleCustomMessageToggle } = useCheckoutEditor();

  function handleToggleMessageBanner(isChecked: boolean) {
    handleCustomMessageToggle(isChecked);
    track.toggleMessageBanner(isChecked ? 'visible' : 'hidden');
  }

  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE].isEnabled}
      feature={CHECKOUT_EDITOR_FIELDS.CUSTOM_MESSAGE}
      title={CUSTOM_MESSAGE_DEFAULT_VALUE.title}
      subTitle={CUSTOM_MESSAGE_DEFAULT_VALUE.subTitle}
      toggleHandler={handleToggleMessageBanner}
      blockData={blockData}
      extraItems={<ExtraItems />}
    />
  );
};

export default CustomMessageSettings;
