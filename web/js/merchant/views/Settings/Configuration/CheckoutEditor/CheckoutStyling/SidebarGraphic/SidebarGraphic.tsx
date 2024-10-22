import React from 'react';

import ExtraItems from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/SidebarGraphic/ExtraItems';
import { SIDEBAR_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import track from './track';

const SidebarGraphic = () => {
  const { values, handleSidebarGraphicToggle } = useCheckoutEditor();

  function handleSidebarGraphicToggleChange(isChecked: boolean) {
    handleSidebarGraphicToggle(isChecked);
    track.toggleSidebarGraphic(isChecked ? 'visible' : 'hidden');
  }

  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled}
      feature={CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC}
      title={SIDEBAR_DEFAULT_VALUE.title}
      subTitle={SIDEBAR_DEFAULT_VALUE.subTitle}
      toggleHandler={handleSidebarGraphicToggleChange}
      extraItems={<ExtraItems />}
    />
  );
};

export default SidebarGraphic;
