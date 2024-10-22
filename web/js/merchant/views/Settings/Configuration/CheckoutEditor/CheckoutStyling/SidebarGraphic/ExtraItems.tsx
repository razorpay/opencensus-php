import React from 'react';

import {
  GraphicWrapper,
  GraphicIconWrapper,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/SidebarGraphic/styled';
import { SIDEBAR_GRAPHICS_ITEMS } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import track from './track';

const ExtraItems = () => {
  const { values, handleSidebarGraphicValueChange } = useCheckoutEditor();

  function handleSidebarGraphicSelect(value: string) {
    handleSidebarGraphicValueChange(value);
    track.selectSidebarGraphicImage(value);
  }

  const isSidebarGraphicEnabled = values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled;

  return (
    <>
      {isSidebarGraphicEnabled && (
        <GraphicWrapper>
          {SIDEBAR_GRAPHICS_ITEMS.map((graphic) => (
            <GraphicIconWrapper
              key={graphic.value}
              onClick={() => handleSidebarGraphicSelect(graphic.value)}
              isSelected={values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC].svg === graphic.value}
            >
              <img src={graphic.src} width="24px" height="24px" alt={graphic.value} />
            </GraphicIconWrapper>
          ))}
        </GraphicWrapper>
      )}
    </>
  );
};

export default ExtraItems;
