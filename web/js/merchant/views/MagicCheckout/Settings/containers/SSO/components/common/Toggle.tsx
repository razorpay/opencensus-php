import React from 'react';

import { Box, Text, Switch, useToast } from '@razorpay/blade/components';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import { getSSOConfigPayload } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/helpers';
import {
  saveSSOSettings,
  updateMerchantTheme,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/api';
import { updateThemeResponseType } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import debounce from 'lodash/debounce';

const SSOToggle = () => {
  const { show } = useToast();
  const {
    isSSOEnabled,
    updateIsSSOEnabled,
    ssoSettings,
    ssoWidget,
    merchantId,
    dashboardView,
    mode,
  } = useSSOContext();

  const handleToggle = async () => {
    try {
      const payload = getSSOConfigPayload(
        { isSSOEnabled: !isSSOEnabled, ssoSettings, ssoWidget },
        merchantId,
      );
      
      // save sso inital configs
      await saveSSOSettings(payload);
      
      // inject sso snippet in merchant theme
      const res: updateThemeResponseType = await updateMerchantTheme(payload, dashboardView, mode);
      
      if (res?.data?.status === 'success') {
        updateIsSSOEnabled(!isSSOEnabled);
        show({
          type: 'informational',
          content: 'SSO configs updated successfully',
          color: 'positive',
        });
      } else {
        throw new Error('SSO configs update failed');
      }
    } catch (error) {
      show({
        type: 'informational',
        content: 'SSO configs update failed',
        color: 'negative',
      });
    }
  };
  const handleChange = debounce(handleToggle, 1000);

  return (
    <Box width="320px" marginBottom="spacing.6">
      <Box
        display="flex"
        borderRadius="medium"
        borderColor="surface.border.gray.subtle"
        justifyContent="space-between"
        alignItems="center"
        borderWidth="thinner"
        paddingY="spacing.4"
        marginTop="spacing.6"
      >
        <Text
          marginRight="spacing.10"
          display="inline-flex"
          marginLeft="spacing.6"
          weight="semibold"
        >
          Enable Login with Razorpay
        </Text>
        <Switch
          accessibilityLabel="Enable Razorpay SSO"
          marginX="spacing.3"
          isChecked={isSSOEnabled}
          onChange={handleChange}
          name="ssoToggle"
        />
      </Box>
    </Box>
  );
};

export default SSOToggle;
