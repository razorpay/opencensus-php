import React, { useState } from 'react';
import {
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Box,
} from '@razorpay/blade/components';

import ShippingSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/ShippingSettingsTab';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import {
  WOOCOMMERCE_SHIPPING_SETTINGS_TYPE,
  WOOC_MAGIC_SHIPPING,
  WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

import {
  AccessabilityToolbar,
  StyledPluginUpdateWrapper,
  DropdownWrapper,
} from 'merchant/views/MagicCheckout/MagicSettings/styled';

const ShippingSettingsWrapper = ({ abExperiments }): JSX.Element => {
  const [settingType, setSettingType] = useState<string>(WOOC_MAGIC_SHIPPING);

  const { magic_shopify_shipping_engine } = abExperiments;
  const isShippingEngineLive = magic_shopify_shipping_engine.variables.result === 'on';

  return (
    <>
      {isShippingEngineLive && settingType === WOOC_MAGIC_SHIPPING ? (
        <Box
          padding="spacing.6"
          backgroundColor="surface.background.gray.intense"
          paddingLeft="spacing.0"
          paddingRight="spacing.0"
        >
          <StyledPluginUpdateWrapper>
            {WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE}
          </StyledPluginUpdateWrapper>
        </Box>
      ) : null}
      {isShippingEngineLive ? (
        <AccessabilityToolbar>
          <DropdownWrapper>
            <Dropdown selectionType="single">
              <SelectInput
                label="Shipping type:"
                labelPosition="left"
                onChange={({ values }) => setSettingType(values[0])}
                placeholder="Choose shipping type"
                validationState="none"
                value={settingType}
                testID="shipping-type"
              />
              <DropdownOverlay>
                <ActionList
                  children={WOOCOMMERCE_SHIPPING_SETTINGS_TYPE.map((option) => {
                    return (
                      <ActionListItem
                        key={option.value}
                        title={option.label}
                        value={option.value}
                      />
                    );
                  })}
                />
              </DropdownOverlay>
            </Dropdown>
          </DropdownWrapper>
        </AccessabilityToolbar>
      ) : null}
      {isShippingEngineLive && settingType === WOOC_MAGIC_SHIPPING ? (
        <ShippingSettingsTab />
      ) : (
        <WoocShippingTab />
      )}
    </>
  );
};

export default ShippingSettingsWrapper;
