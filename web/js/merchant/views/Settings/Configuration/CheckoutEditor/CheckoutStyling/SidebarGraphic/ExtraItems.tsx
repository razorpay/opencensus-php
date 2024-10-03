import React from 'react';

import {
  GraphicWrapper,
  GraphicIconWrapper,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/SidebarGraphic/styled';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { SIDEBAR_GRAPHICS_ITEMS } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

const ExtraItems = () => {
  const { values, handleSidebarGraphicValueChange } = useCheckoutEditor();

  const isSidebarGraphicEnabled = values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled;

  return (
    <>
      {isSidebarGraphicEnabled && (
        <GraphicWrapper>
          {SIDEBAR_GRAPHICS_ITEMS.map((graphic) => (
            <GraphicIconWrapper
              key={graphic.value}
              onClick={() => handleSidebarGraphicValueChange(graphic.value)}
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
