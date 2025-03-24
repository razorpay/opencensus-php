import React from 'react';
import { Box, Card, CardBody, Radio, RadioGroup, SlashIcon } from '@razorpay/blade/components';

import {
  AVAILABLE_GRAPHICS,
  SIDEBAR_GRAPHICS_ITEMS,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

import track from './track';

const GraphicWrapper = ({ children }) => (
  <Box display="flex" alignItems="center" marginTop="spacing.2" gap="spacing.3">
    {children}
  </Box>
);

const ExtraItems = () => {
  const { values, handleSidebarGraphicValueChange } = useCheckoutEditor();

  function handleSidebarGraphicSelect(value: string) {
    handleSidebarGraphicValueChange(value);
    track.selectSidebarGraphicImage(value);
  }

  const isSidebarGraphicEnabled = values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.enabled;
  const selectedGraphic = values[CHECKOUT_EDITOR_FIELDS.SIDEBAR_GRAPHIC]?.svg ?? '';

  return isSidebarGraphicEnabled ? (
    <GraphicWrapper>
      <RadioGroup onChange={({ value }) => handleSidebarGraphicSelect(value)}>
        <GraphicWrapper>
          {SIDEBAR_GRAPHICS_ITEMS.map((graphic) => (
            <Card
              elevation="none"
              key={graphic.value}
              as="label"
              accessibilityLabel={
                graphic.value === selectedGraphic ? `${graphic.value}-selected` : graphic.value
              }
              testID={`test-sidebar-graphic-${graphic.value}`}
              isSelected={graphic.value === selectedGraphic}
              height="48px"
              width="48px"
              padding="spacing.0"
            >
              <CardBody>
                <Box
                  display="flex"
                  height="48px"
                  width="48px"
                  alignSelf="center"
                  justifyContent="center"
                  alignItems="center"
                >
                  <Radio
                    value={graphic.value}
                    visibility="hidden"
                    display="none"
                    testID={`radio-${graphic.value}`}
                  />
                  {graphic.value === AVAILABLE_GRAPHICS.NONE ? (
                    <SlashIcon color="surface.icon.gray.disabled" size="xlarge" />
                  ) : (
                    <img src={graphic.src} width="48px" height="48px" alt={graphic.value} />
                  )}
                </Box>
              </CardBody>
            </Card>
          ))}
        </GraphicWrapper>
      </RadioGroup>
    </GraphicWrapper>
  ) : null;
};

export default ExtraItems;
