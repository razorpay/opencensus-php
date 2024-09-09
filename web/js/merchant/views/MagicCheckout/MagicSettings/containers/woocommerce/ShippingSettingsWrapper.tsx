import React, { useState } from 'react';
import {
  Dropdown,
  DropdownOverlay,
  SelectInput,
  RadioGroup,
  Radio,
  ActionList,
  ActionListItem,
  Box,
  Heading,
  Divider,
} from '@razorpay/blade/components';
import { COMMON_Z_INDEX } from 'common/constant';
import ShippingSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/ShippingSettingsTab';
import WoocShippingTab from 'merchant/views/MagicCheckout/MagicSettings/containers/woocommerce/ShippingWrapper';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import {
  WOOCOMMERCE_SHIPPING_SETTINGS_TYPE,
  WOOC_MAGIC_SHIPPING,
  WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE,
  WOOC_SHIPPING_ENGINE_DESC,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';

import {
  AccessabilityToolbar,
  StyledPluginUpdateWrapper,
  DropdownWrapper,
} from 'merchant/views/MagicCheckout/MagicSettings/styled';

const ShippingSettingsWrapper = ({ abExperiments }): JSX.Element => {
  const [settingType, setSettingType] = useState<string>(WOOC_MAGIC_SHIPPING);

  const { magic_shopify_shipping_engine } = abExperiments;
  const isShippingEngineLive = magic_shopify_shipping_engine.variables.result === 'on';

  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);

  return (
    <>
      {isShippingEngineLive ? (
        <Box
          padding="spacing.6"
          backgroundColor="surface.background.gray.intense"
          paddingLeft="spacing.0"
          paddingRight="spacing.0"
        >
          {settingType === WOOC_MAGIC_SHIPPING ? (
            <StyledPluginUpdateWrapper>
              {WOOC_SHIPPING_ENGINE_PLUGIN_UPDATE}
            </StyledPluginUpdateWrapper>
          ) : (
            <StyledPluginUpdateWrapper>{WOOC_SHIPPING_ENGINE_DESC}</StyledPluginUpdateWrapper>
          )}
        </Box>
      ) : null}
      <Divider />
      {isShippingEngineLive ? (
        isMagicDashboardV2Enabled ? (
          <Box display="flex" marginY="spacing.7">
            <Heading marginX="spacing.7">Shipping Type</Heading>
            <RadioGroup onChange={({ value }) => setSettingType(value)} defaultValue={settingType}>
              {WOOCOMMERCE_SHIPPING_SETTINGS_TYPE?.map((shippingType) => (
                <Radio key={shippingType?.value} value={shippingType?.value}>
                  {shippingType?.label}
                </Radio>
              ))}
            </RadioGroup>
          </Box>
        ) : (
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
                <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
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
        )
      ) : null}
      <Divider />
      {isShippingEngineLive && settingType === WOOC_MAGIC_SHIPPING ? (
        <ShippingSettingsTab />
      ) : (
        <WoocShippingTab />
      )}
    </>
  );
};

export default ShippingSettingsWrapper;
