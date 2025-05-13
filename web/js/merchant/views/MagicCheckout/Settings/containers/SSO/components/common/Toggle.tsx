import React, { useState } from 'react';

import { Box, Switch, Text } from '@razorpay/blade/components';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import SSOToggleModal from './SSOToggleModal';

const SSOToggle = () => {
  const { isSSOEnabled } = useSSOContext();
  const [showModal, setShowModal] = useState(false);

  const handleToggle = () => {
    setShowModal(true);
  };

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
          onChange={handleToggle}
          name="ssoToggle"
        />
        <SSOToggleModal isOpen={showModal} onDismiss={() => setShowModal(false)} />
      </Box>
    </Box>
  );
};

export default SSOToggle;
